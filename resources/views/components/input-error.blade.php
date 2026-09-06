@props([
    'messages',
    'id' => null,
])

@if ($messages)
    <ul
        @if($id) id="{{ $id }}" @endif
        role="alert"
        {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}
    >
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
