# Sewa Apartemen CMS

A full-featured apartment rental management system built with Laravel 13.

## Features

- **Property Management**: CRUD for properties with address, city, province, status
- **Unit Management**: Units per property with type, floor, size, bedrooms, bathrooms, pricing
- **Amenities**: Manage amenities and assign to units
- **Booking System**: Public booking form with date picker, guest counter, booking codes (BK-YYYYMMDD-XXXX), status workflow (pending → confirmed → completed / cancelled), admin notes, CSV export
- **Blog**: Posts, categories, tags with rich text editor and featured images
- **SEO Engine**: Meta tags, Open Graph, Twitter Cards, JSON-LD structured data, auto-generated sitemap.xml, robots.txt, redirect manager with loop detection
- **Analytics**: GA4, Google Tag Manager, Meta Pixel, Microsoft Clarity, Google Search Console verification
- **Pages & Blocks**: Custom pages with content blocks (hero, rich text, image, gallery)
- **Navigation**: Multi-location menu builder (main, footer, sidebar) with drag-to-reorder
- **Media Library**: File upload and management
- **Settings**: Site configuration (general, contact, analytics, social, SEO, booking)
- **Web Installer**: 5-step guided installation
- **User Management**: Admin accounts with role assignment
- **Security**: CSRF, X-Frame-Options, X-Content-Type-Options, mass assignment protection

## Quick Start

### Requirements

- PHP 8.3+
- MySQL 8.0+ / MariaDB 10.6+
- Node.js 18+
- Composer 2.x

### Installation

```bash
# Clone and install
git clone <repo-url> sewa-apartemen-cms
cd sewa-apartemen-cms
composer setup

# Or step by step:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan storage:link

# Start dev server
composer dev
```

Open http://localhost:8000/install to run the web installer, or http://localhost:8000 to view the site.

### Default Admin

After seeding:
- Email: `admin@admin.com`
- Password: `password`

## Documentation

| Document | Description |
|----------|-------------|
| [User Guide](docs/USER_GUIDE.md) | How to browse properties, book units, read blog |
| [Admin Guide](docs/ADMIN_GUIDE.md) | Managing properties, units, bookings, pages, blog, settings |
| [Developer Guide](docs/DEVELOPER_GUIDE.md) | Code structure, services, caching, testing, extending |
| [FAQ](docs/FAQ.md) | Common questions and answers |
| [Troubleshooting](docs/TROUBLESHOOTING.md) | Fix common issues |
| [Deployment](docs/DEPLOYMENT.md) | Production deployment checklist |
| [Architecture](docs/ARCHITECTURE.md) | System architecture overview |
| [Database](docs/DATABASE.md) | Database schema |
| [SEO](docs/SEO.md) | SEO architecture |
| [Security](docs/SECURITY.md) | Security measures |
| [Analytics](docs/ANALYTICS.md) | Analytics integrations |
| [Booking](docs/BOOKING.md) | Booking flow |
| [Testing](docs/TESTING.md) | Testing strategy |
| [Roadmap](docs/ROADMAP.md) | Implementation phases |

## Testing

```bash
php artisan test
```

174 tests, 414 assertions. All passing.

## Tech Stack

- Backend: Laravel 13.8, PHP 8.3
- Frontend: Blade, Tailwind CSS, Alpine.js
- Database: MySQL 8 / MariaDB
- Build: Vite

## License

MIT
