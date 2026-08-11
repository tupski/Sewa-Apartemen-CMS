# Admin Guide — Sewa Apartemen CMS

Access the admin panel at `/login`, then navigate to `/admin`.

## Dashboard

The dashboard shows key statistics: total properties, units, bookings, and recent activity.

## Properties

**Admin > Properties**

- **List**: View all properties with search and status filter.
- **Create**: Add name, slug, description, address, city, province, postal code, status.
- **Edit/Delete**: Manage existing properties. Deleting a property removes all its units.

SEO metadata can be set per property (meta title, description, canonical URL, Open Graph image, index status).

## Units

**Admin > Units**

- **List**: View all units across properties with filters by property, status, and search.
- **Create**: Assign unit to a property, set type (Studio/1BR/2BR/3BR), floor, size, bedrooms, bathrooms, pricing (per night/month/year), and amenities.
- **Status**: Available → Booked → Maintenance. Toggle via inline controls.

## Amenities

**Admin > Amenities**

Manage the list of amenities (e.g., WiFi, AC, Parking, Pool). Assign amenities to units via the unit edit form.

## Bookings

**Admin > Bookings**

- **List**: All booking requests with customer details, dates, status, and booking code.
- **Status Workflow**: Pending → Confirmed → Completed. Or Pending → Cancelled.
- **Actions**: Confirm, Cancel, Complete, Add Notes.
- **Export**: Download CSV of all bookings.

## Pages

**Admin > Pages**

Create custom pages (About Us, Contact, Terms). Each page has a title, slug, content (rich text), and status (published/draft). Pages appear in the sitemap when published.

## Blocks

**Admin > Blocks**

Reusable content blocks for the frontend. Types: hero, rich text, image, gallery, etc. Assign blocks to pages or use in theme layouts.

## Navigation

**Admin > Navigations**

Build menus for three locations: **Main Menu**, **Footer**, **Sidebar**. Each item can link to a page or an external URL. Drag-to-reorder supported. Set status to active/inactive.

## Blog

**Admin > Posts**: Write articles with title, slug, body (rich text), featured image, category, tags, status, and published date.
**Admin > Categories**: Manage blog categories.
**Admin > Tags**: Manage blog tags.

## Users

**Admin > Users**

Manage admin accounts. Create, edit, delete users. Assign roles (Super Admin, Editor, Booking Staff, SEO Manager).

## Media

**Admin > Media**

Upload and manage images and files. Supported formats: jpg, png, gif, webp, pdf. Upload via drag-and-drop or file picker.

## Settings

**Admin > Settings**

Site configuration grouped into tabs:

- **General**: Site name, tagline, logo, favicon, footer text.
- **Contact**: Email, phone, WhatsApp, address.
- **Analytics**: Google Analytics 4 ID, GTM container ID, Meta Pixel ID, Microsoft Clarity ID, Search Console token.
- **Social**: Facebook, Instagram, Twitter/X, LinkedIn, YouTube URLs.
- **SEO**: Default meta title/description, robots.txt override, currency for structured data.
- **Booking**: Default check-in/out times, WhatsApp number for notifications.

## Redirects

**Admin > Redirects**

Manage 301/302 redirects. Add `from_url` → `to_url` pairs. The system automatically prevents redirect loops (detects cycles and returns 404).

## SEO

SEO is handled automatically for all content types:
- **Meta tags**: Title (truncated to 60 chars), description (truncated to 160 chars), canonical URL, robots.
- **Open Graph / Twitter Cards**: Per-content OG title, description, image.
- **JSON-LD**: Organization, WebSite, RealEstateListing, Offer, Article, BreadcrumbList schemas.
- **Sitemap**: Auto-generated at `/sitemap.xml`. Includes homepage, properties, units, pages, blog posts.
- **Robots.txt**: Auto-generated at `/robots.txt`. Disallows admin, login, register, dashboard, profile.
