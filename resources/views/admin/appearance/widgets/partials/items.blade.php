@foreach ($widgets as $widget)
    @include('admin.appearance.widgets.partials.card', ['widget' => $widget])
@endforeach
