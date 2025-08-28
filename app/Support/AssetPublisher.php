<?php


namespace App\Support;


use Symfony\Component\Filesystem\Filesystem;


class AssetPublisher
{
    /** Mirror a /dist folder to a public target with a freshness check */
    public function mirror(string $fromDist, string $toPublic): bool
    {
        if (!is_dir($fromDist))
            return false;
        @mkdir($toPublic, 0775, true);
        $fs = new Filesystem();


        $srcMan = $fromDist . '/manifest.json';
        $dstMan = $toPublic . '/manifest.json';
        if (is_file($srcMan) && is_file($dstMan)) {
            if (filesize($srcMan) === @filesize($dstMan) && filemtime($srcMan) <= @filemtime($dstMan)) {
                return true; // fresh
            }
        }


        // clean dest and mirror
        if (is_dir($toPublic)) {
            foreach (glob($toPublic . '/*') as $f)
                $fs->remove($f);
        }
        $fs->mirror($fromDist, $toPublic);
        return true;
    }
}