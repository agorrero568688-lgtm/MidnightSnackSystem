@props(['image' => null, 'name' => '', 'category' => null])

@php
    $categoryKey = mb_strtolower((string) $category);

    $theme = match (true) {
        str_contains($categoryKey, 'drink'),
        str_contains($categoryKey, 'beverage'),
        str_contains($categoryKey, 'juice'),
        str_contains($categoryKey, 'coffee'),
        str_contains($categoryKey, 'tea'),
        str_contains($categoryKey, 'shake'),
        str_contains($categoryKey, 'soda'),
        str_contains($categoryKey, 'water'),
        str_contains($categoryKey, 'milk') => [
            'gradient' => 'from-sky-300 via-cyan-200 to-sky-100',
            'icon' => 'cup',
            'ink' => 'text-sky-900/60',
            'glow' => 'bg-sky-400/30',
        ],
        str_contains($categoryKey, 'snack'),
        str_contains($categoryKey, 'dessert'),
        str_contains($categoryKey, 'cake'),
        str_contains($categoryKey, 'sweet'),
        str_contains($categoryKey, 'pastry'),
        str_contains($categoryKey, 'cookie'),
        str_contains($categoryKey, 'halo'),
        str_contains($categoryKey, 'merienda'),
        str_contains($categoryKey, 'ice cream') => [
            'gradient' => 'from-rose-300 via-amber-200 to-rose-100',
            'icon' => 'cookie',
            'ink' => 'text-rose-900/60',
            'glow' => 'bg-rose-400/30',
        ],
        str_contains($categoryKey, 'noodle'),
        str_contains($categoryKey, 'soup'),
        str_contains($categoryKey, 'meal'),
        str_contains($categoryKey, 'rice'),
        str_contains($categoryKey, 'combo'),
        str_contains($categoryKey, 'viand'),
        str_contains($categoryKey, 'ulam'),
        str_contains($categoryKey, 'silog'),
        str_contains($categoryKey, 'pancit'),
        str_contains($categoryKey, 'lomi'),
        str_contains($categoryKey, 'mami'),
        str_contains($categoryKey, 'burger'),
        str_contains($categoryKey, 'sandwich'),
        str_contains($categoryKey, 'chicken'),
        str_contains($categoryKey, 'pork'),
        str_contains($categoryKey, 'beef'),
        str_contains($categoryKey, 'fish') => [
            'gradient' => 'from-amber-300 via-brand-200 to-amber-100',
            'icon' => 'bowl',
            'ink' => 'text-amber-900/60',
            'glow' => 'bg-amber-400/30',
        ],
        default => [
            'gradient' => 'from-brand-300 via-brand-200 to-violet-200',
            'icon' => 'utensils',
            'ink' => 'text-brand-900/60',
            'glow' => 'bg-brand-400/30',
        ],
    };

    $initial = mb_strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
@endphp

@if ($image)
    <img src="{{ $image }}" alt="{{ $name }}" loading="lazy" decoding="async"
        {{ $attributes->merge(['class' => 'h-full w-full object-cover transition duration-500 group-hover:scale-105']) }}>
@else
    <div
        {{ $attributes->merge(['class' => 'relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br transition duration-500 group-hover:scale-105 '.$theme['gradient']]) }}>
        <div class="absolute -top-6 -right-4 h-24 w-24 rounded-full {{ $theme['glow'] }} blur-2xl"></div>
        <div class="absolute -bottom-8 -left-4 h-24 w-24 rounded-full bg-white/50 blur-2xl"></div>

        <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-white/55 shadow-sm ring-1 ring-white/70 backdrop-blur-sm">
            <x-icon :name="$theme['icon']" class="h-10 w-10 {{ $theme['ink'] }}" />
        </span>

        <span class="absolute right-3 bottom-0 text-6xl leading-none font-black text-ink-950/10 select-none">
            {{ $initial }}
        </span>
    </div>
@endif
