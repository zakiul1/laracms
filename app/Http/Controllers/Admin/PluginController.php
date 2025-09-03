<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\PluginSetting;
use App\Services\PluginLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PluginController extends Controller
{
    public function __construct(protected PluginLoader $loader)
    {
    }

    public function index()
    {
        $plugins = Plugin::orderBy('name')->get();
        return view('admin.plugins.index', compact('plugins'));
    }

    public function sync()
    {
        $this->loader->scanAndSync();
        return redirect()->route('admin.plugins.index')->with('success', 'Plugins synchronized.');
    }

    public function upload(Request $req)
    {
        $req->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:' . (config('plugins.max_upload_mb', 50) * 1024)],
        ]);

        $path = $req->file('zip')->store('tmp/uploads', 'local');
        $abs = storage_path('app/' . $path);

        $plugin = $this->loader->installFromZip($abs);

        // delete temp
        File::delete($abs);

        return redirect()->route('admin.plugins.index')->with('success', "Installed: {$plugin->name}");
    }

    public function activate(Plugin $plugin)
    {
        // TODO: dependency checks if needed (version constraints)
        $plugin->enabled = true;
        $plugin->save();

        return back()->with('success', "Activated {$plugin->name}");
    }

    public function deactivate(Plugin $plugin)
    {
        $plugin->enabled = false;
        $plugin->save();

        return back()->with('success', "Deactivated {$plugin->name}");
    }

    public function destroy(Plugin $plugin)
    {
        DB::transaction(function () use ($plugin) {
            // call uninstall + remove files
            $this->loader->uninstall($plugin);
            // remove DB rows
            $plugin->settings()->delete();
            $plugin->delete();
        });

        return redirect()->route('admin.plugins.index')->with('success', 'Plugin deleted.');
    }

    public function updateUpload(Request $req, Plugin $plugin)
    {
        $req->validate([
            'zip' => ['required', 'file', 'mimes:zip', 'max:' . (config('plugins.max_upload_mb', 50) * 1024)],
        ]);

        $path = $req->file('zip')->store('tmp/uploads', 'local');
        $abs = storage_path('app/' . $path);

        $this->loader->updateFromZip($plugin, $abs);

        File::delete($abs);
        return back()->with('success', "Updated {$plugin->name}");
    }

    public function updateRemote(Plugin $plugin, Request $req)
    {
        $url = $req->input('url') ?: $plugin->update_url;
        if (!$url)
            return back()->with('error', 'No update URL.');

        $tmp = storage_path('app/tmp/' . uniqid('plg_', true) . '.zip');
        File::ensureDirectoryExists(dirname($tmp));

        $res = Http::timeout(60)->get($url);
        if (!$res->ok())
            return back()->with('error', 'Failed to download update.');

        File::put($tmp, $res->body());
        $this->loader->updateFromZip($plugin, $tmp);
        File::delete($tmp);

        return back()->with('success', "Updated {$plugin->name} from remote.");
    }

    public function settings(Plugin $plugin)
    {
        $meta = $this->loader->readMetadata($plugin->getBasePath());
        // if plugin ships a settings view, render it
        $customView = $meta['settings_view'] ?? null;
        $pairs = $plugin->settings()->orderBy('key')->get()->keyBy('key');
        return view('admin.plugins.settings', compact('plugin', 'customView', 'pairs', 'meta'));
    }

    public function saveSettings(Plugin $plugin, Request $req)
    {
        $data = $req->except(['_token']);
        foreach ($data as $key => $val) {
            PluginSetting::updateOrCreate(
                ['plugin_id' => $plugin->id, 'key' => $key],
                ['value' => is_array($val) ? $val : (string) $val]
            );
        }
        return back()->with('success', 'Settings saved.');
    }

    /** Optional: download a plugin as zip (export) */
    public function export(Plugin $plugin): StreamedResponse
    {
        $zipPath = storage_path('app/tmp/' . Str::slug($plugin->slug) . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $base = $plugin->getBasePath();
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $local = ltrim(str_replace($base, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            if ($file->isDir())
                $zip->addEmptyDir($local);
            else
                $zip->addFile($file->getPathname(), $local);
        }
        $zip->close();

        return response()->streamDownload(function () use ($zipPath) {
            echo file_get_contents($zipPath);
            @unlink($zipPath);
        }, $plugin->slug . '.zip');
    }
}