@php
    $seoData = $seo ?? \App\Services\SeoService::metaTagsArray($model ?? null);
@endphp
{!! \App\Services\SeoService::renderMetaTags($seoData) !!}
