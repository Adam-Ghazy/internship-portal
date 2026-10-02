@props(['variant' => 'default', 'href' => null, 'full' => false])

@php
$classes = 'btn' . ($variant === 'primary' ? ' btn-primary' : '') . ($full ? ' btn-full' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>{{ $slot }}</button>
@endif
