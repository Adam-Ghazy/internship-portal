@props(['name' => '', 'size' => 'md'])

@php
    $words = preg_split('/\s+/', trim((string) $name));
    $initial = count($words) > 1
        ? mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1))
        : mb_strtoupper(mb_substr(trim((string) $name), 0, 2) ?: '?');
    $sizes = [
        'sm' => 'h-8 w-8 text-[11px]',
        'md' => 'h-10 w-10 text-xs',
        'lg' => 'h-12 w-12 text-sm',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-primary-soft font-bold text-primary-hover ' . ($sizes[$size] ?? $sizes['md'])]) }} aria-hidden="true">{{ $initial }}</span>