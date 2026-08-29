{{--
    "Powered by Artivo CMS vX.Y.Z" credit line.

    Version comes from the single source of truth: config('artivo.version')
    (see config/artivo.php). Never hardcode the version here.

    Usage:
        <x-powered-by />                             default styling (dark footer)
        <x-powered-by class="text-gray-500" :link-class="'hover:text-gray-900'"/>
--}}
@props(['linkClass' => 'font-medium underline underline-offset-2 hover:text-white transition'])

<p {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1']) }}>
    <span>{{ __('footer.powered_by') }}</span>
    <a href="{{ config('artivo.url') }}"
       target="_blank"
       rel="noopener noreferrer"
       class="{{ $linkClass }} focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-current rounded">{{ config('artivo.product') }}</a>
    <span>v{{ config('artivo.version') }}</span>
</p>
