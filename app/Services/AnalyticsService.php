<?php

namespace App\Services;

class AnalyticsService
{
    /**
     * Render GA4 (gtag) inline script.
     */
    public static function ga4Script(): string
    {
        $id = SettingsService::get('google_analytics_id');
        if (empty($id)) {
            return '';
        }

        return <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$id}');
</script>
<!-- End Google Analytics 4 -->
HTML;
    }

    /**
     * Render GTM <head> snippet.
     */
    public static function gtmScript(): string
    {
        $id = SettingsService::get('google_tag_manager_id');
        if (empty($id)) {
            return '';
        }

        return <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$id}');</script>
<!-- End Google Tag Manager -->
HTML;
    }

    /**
     * Render GTM <noscript> fallback for <body>.
     */
    public static function gtmNoScript(): string
    {
        $id = SettingsService::get('google_tag_manager_id');
        if (empty($id)) {
            return '';
        }

        return <<<HTML
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$id}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;
    }

    /**
     * Render Meta Pixel base code + noscript.
     */
    public static function metaPixelScript(): string
    {
        $id = SettingsService::get('meta_pixel_id');
        if (empty($id)) {
            return '';
        }

        return <<<HTML
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$id}');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
HTML;
    }

    /**
     * Render Microsoft Clarity tracking script.
     */
    public static function clarityScript(): string
    {
        $id = SettingsService::get('microsoft_clarity_id');
        if (empty($id)) {
            return '';
        }

        return <<<HTML
<!-- Microsoft Clarity -->
<script type="text/javascript">
(function(c,l,a,r,i,t,y){
c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", "{$id}");
</script>
<!-- End Microsoft Clarity -->
HTML;
    }

    /**
     * Render Google Search Console verification meta tag.
     */
    public static function searchConsoleMeta(): string
    {
        $token = SettingsService::get('search_console_token');
        if (empty($token)) {
            return '';
        }

        return '<meta name="google-site-verification" content="' . e($token) . '">';
    }

    /**
     * Check if any analytics integration is configured.
     */
    public static function hasAny(): bool
    {
        return !empty(SettingsService::get('google_analytics_id'))
            || !empty(SettingsService::get('google_tag_manager_id'))
            || !empty(SettingsService::get('meta_pixel_id'))
            || !empty(SettingsService::get('microsoft_clarity_id'))
            || !empty(SettingsService::get('search_console_token'));
    }
}
