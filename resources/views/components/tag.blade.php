@props(['variant' => 'default'])

@php
$map = ['default' => 'tag', 'neutral' => 'tag tag-neutral', 'green' => 'tag tag-green'];
@endphp

<span {{ $attributes->merge(['class' => $map[$variant] ?? 'tag']) }}>{{ $slot }}</span>
