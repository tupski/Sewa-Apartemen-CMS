# FAQ — Sewa Apartemen CMS

## How do I change the site logo?

Go to **Admin > Settings > General**. Upload a new logo image. The logo appears in the header and in JSON-LD Organization schema.

## How do I add a custom page?

Go to **Admin > Pages > Create**. Enter the page title, slug (auto-generated from title), content, and set status to "Published". The page will appear in the main navigation if you add it via **Admin > Navigations**.

## How do I change the homepage content?

The homepage is built from blocks. Go to **Admin > Blocks** and edit the "hero" or "featured-properties" blocks. Changes appear immediately on the frontend.

## How do I set up Google Analytics?

Go to **Admin > Settings > Analytics**. Paste your GA4 Measurement ID (e.g., `G-XXXXXXXXXX`). The tracking code will be injected into all public pages automatically. Admin pages are excluded.

## How do I set up Google Tag Manager?

Go to **Admin > Settings > Analytics**. Paste your GTM Container ID (e.g., `GTM-XXXXXXX`). Both `<head>` and `<noscript>` tags are injected.

## How do I set up Meta Pixel?

Go to **Admin > Settings > Analytics**. Paste your Meta Pixel ID. The pixel fires `PageView` on all public pages and `Purchase` on the booking success page.

## How do I manage redirects?

Go to **Admin > Redirects**. Add entries:
- **From URL**: The old path (without domain), e.g., `old-page`
- **To URL**: The new destination, e.g., `https://example.com/new-page` or `/new-page`
- **Status Code**: 301 (permanent) or 302 (temporary)

The system prevents redirect loops automatically.

## How do I update the sitemap?

The sitemap at `/sitemap.xml` updates automatically. If you need a fresh copy immediately, run:
```bash
php artisan cache:clear
```

## How do I change robots.txt?

By default, `robots.txt` is auto-generated. To override it, go to **Admin > Settings > SEO** and paste your custom robots.txt content into the "Robots.txt Override" field.

## How do I change the currency?

Go to **Admin > Settings > SEO**. Change the "Currency" field (default: IDR). This affects JSON-LD Offer schema pricing.

## How do I export bookings?

Go to **Admin > Bookings**. Click the "Export CSV" button. A CSV file downloads with all booking data.

## How do I make a unit unavailable?

Change the unit status to "Maintenance" or "Booked" via **Admin > Units**. The unit won't show in booking forms.

## How do I remove the "Powered by" or customize the footer?

Go to **Admin > Settings > General**. Edit the "Footer Text" field. HTML is allowed.
