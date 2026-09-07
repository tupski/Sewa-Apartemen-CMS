@php
    /**
     * Responsive Media image with generated variants.
     *
     * Renders a <picture> element with AVIF/WebP sources (when the Media
     * record has variants from ImageVariantService) plus a srcset ladder on
     * the original format. Without variants it degrades to a plain <img>.
     *
     * Intrinsic width/height attributes are rendered ONLY from real media
     * metadata (CLS safety) — never fabricated.
     *
     * Required props:
     *   - media  \App\Models\Media|null  the media record (renders nothing when null)
     *
     * Optional props:
     *   - alt    string  alt text override (falls back to media alt/title)
     *   - eager  bool    LCP image: loading="eager" + fetchpriority="high"
     *   - sizes  string  sizes attribute passed to srcset-aware browsers
     *
     * Extra attributes (class, draggable, data-*) are forwarded to the <img>.
     */
    $media = $media ?? null;
    $eager = $eager ?? false;
    $sizes = $sizes ?? null;
    $alt = $alt ?? null;
@endphp
@if ($media)
    @php
        $srcset = $media->srcsetFor('original');
        $webpSrcset = $media->srcsetFor('webp');
        $avifSrcset = $media->srcsetFor('avif');
        $defaultAlt = trim((string) ($media->alt ?? ''));
        if ($defaultAlt === '') {
            $defaultAlt = trim((string) ($media->title ?? ''));
        }
        $altText = trim((string) ($alt ?? '')) !== '' ? trim((string) $alt) : $defaultAlt;
        $hasDims = ! empty($media->width) && ! empty($media->height);
    @endphp
    <picture>
        @if ($avifSrcset)
            <source type="image/avif" srcset="{{ $avifSrcset }}"@if (! empty($sizes)) sizes="{{ $sizes }}"@endif>
        @endif
        @if ($webpSrcset)
            <source type="image/webp" srcset="{{ $webpSrcset }}"@if (! empty($sizes)) sizes="{{ $sizes }}"@endif>
        @endif
        <img src="{{ $media->url }}"
             @if ($srcset) srcset="{{ $srcset }}"@if (! empty($sizes)) sizes="{{ $sizes }}"@endif @endif
             @if ($hasDims) width="{{ $media->width }}" height="{{ $media->height }}" @endif
             @if ($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif
             decoding="async"
             {{ $attributes->except(['alt', 'loading', 'fetchpriority', 'src', 'srcset', 'width', 'height']) }}
             alt="{{ $altText }}">
    </picture>
@endif
