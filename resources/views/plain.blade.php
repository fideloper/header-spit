App Url: {{ url('/') }}
@foreach(request()->headers->all() as $name => $values)
@if($name == "cookie") @continue @endif
{{ $name }}:  {{ implode(', ', $values) }}
@endforeach