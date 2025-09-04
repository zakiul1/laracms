@php $flashTypes = ['success','error','warning','info','status']; @endphp
@foreach ($flashTypes as $t)
    @if (session($t))
        <script>
            window.addEventListener('load', () => {
                window.showToast(@json(session($t)), '{{ $t === 'status' ? 'success' : $t }}');
            });
        </script>
    @endif
@endforeach

@if ($errors->any())
    <script>
        window.addEventListener('load', () => {
            window.showToast(@json($errors->first()), 'error');
        });
    </script>
@endif
