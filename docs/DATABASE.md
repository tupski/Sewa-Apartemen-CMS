# Database Schema

## Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email
        timestamp email_verified_at
        string password
        string remember_token
        string phone
        string avatar
        timestamp last_login
        timestamp created_at
        timestamp updated_at
    }

    roles {
        bigint id PK
        string name
        string slug
        string description
        json permissions
        timestamp created_at
        timestamp updated_at
    }

    model_has_roles {
        bigint role_id FK
        string model_type
        bigint model_id FK
    }

    model_has_permissions {
        bigint permission_id FK
        string model_type
        bigint model_id FK
    }

    settings {
        bigint id PK
        string key
        string value
        string type
        text description
        timestamp created_at
        timestamp updated_at
    }

    media {
        bigint id PK
        bigint user_id FK
        string disk
        string directory
        string filename
        string original_filename
        string mime_type
        string extension
        integer size
        integer width
        integer height
        string type
        string alt
        string title
        string caption
        text description
        json metadata
        timestamp created_at
        timestamp updated_at
    }

    media_folders {
        bigint id PK
        string name
        string slug
        string path
        boolean is_default
        timestamp created_at
        timestamp updated_at
    }

    properties {
        bigint id PK
        bigint user_id FK
        bigint location_id FK
        string name
        string slug
        string short_description
        text description
        string address
        string city
        string province
        string postal_code
        float latitude
        float longitude
        string whatsapp_number
        string phone
        string email
        json amenities
        json features
        string status
        boolean featured
        integer sort_order
        timestamp created_at
        timestamp updated_at
    }

    units {
        bigint id PK
        bigint property_id FK
        bigint user_id FK
        string name
        string slug
        string type
        string short_description
        text description
        integer bedrooms
        integer bathrooms
        integer max_guests
        float size
        decimal price
        string price_type
        string status
        boolean featured
        json amenities
        json images
        string whatsapp_number
        string phone
        timestamp created_at
        timestamp updated_at
    }

    amenities {
        bigint id PK
        string name
        string slug
        string icon
        string description
        string color
        timestamp created_at
        timestamp updated_at
    }

    amenity_property {
        bigint amenity_id FK
        bigint property_id FK
        timestamp created_at
        timestamp updated_at
    }

    amenity_unit {
        bigint amenity_id FK
        bigint unit_id FK
        timestamp created_at
        timestamp updated_at
    }

    pages {
        bigint id PK
        bigint user_id FK
        string title
        string slug
        string excerpt
        text content
        string status
        boolean is_homepage
        string layout
        json blocks
        timestamp created_at
        timestamp updated_at
    }

    page_blocks {
        bigint id PK
        bigint page_id FK
        string type
        json config
        integer sort_order
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    posts {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        string title
        string slug
        string excerpt
        text content
        string status
        string featured_image
        boolean is_sticky
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    categories {
        bigint id PK
        string name
        string slug
        string description
        integer parent_id
        timestamp created_at
        timestamp updated_at
    }

    tags {
        bigint id PK
        string name
        string slug
        timestamp created_at
        timestamp updated_at
    }

    post_category {
        bigint post_id FK
        bigint category_id FK
        timestamp created_at
        timestamp updated_at
    }

    post_tag {
        bigint post_id FK
        bigint tag_id FK
        timestamp created_at
        timestamp updated_at
    }

    locations {
        bigint id PK
        bigint user_id FK
        string name
        string slug
        string description
        string city
        string province
        float latitude
        float longitude
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    bookings {
        bigint id PK
        bigint property_id FK
        bigint unit_id FK
        string booking_code
        string name
        string phone
        string email
        date check_in
        date check_out
        integer guests
        text notes
        string status
        string landing_page
        string utm_source
        string utm_medium
        string utm_campaign
        string utm_term
        string utm_content
        string whatsapp_number
        string whatsapp_url
        timestamp created_at
        timestamp updated_at
    }

    seo_metadata {
        bigint id PK
        string model_type
        bigint model_id FK
        string seo_title
        string seo_description
        string canonical_url
        string og_title
        string og_description
        string og_image
        string twitter_title
        string twitter_description
        string twitter_image
        string robots
        timestamp created_at
        timestamp updated_at
    }

    redirects {
        bigint id PK
        string from_url
        string to_url
        integer status_code
        boolean is_active
        integer hit_count
        timestamp created_at
        timestamp updated_at
    }

    integrations {
        bigint id PK
        string name
        string slug
        json config
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        string model_type
        bigint model_id
        json old_values
        json new_values
        string ip_address
        string user_agent
        timestamp created_at
        timestamp updated_at
    }

    migrations {
        int id PK
        string migration
        int batch
        timestamp created_at
    }

    cache {
        string key
        string value
        string group
        integer ttl
    }

    cache_locks {
        string key
        string owner
        integer ttl
    }

    sessions {
        string id
        bigint user_id
        string ip_address
        string user_agent
        json payload
        integer last_activity
    }

    jobs {
        bigint id PK
        string queue
        json payload
        integer attempts
        bigint reserved_at
        bigint available_at
        bigint created_at
    }

    failed_jobs {
        bigint id PK
        string uuid
        string connection
        string queue
        json payload
        string exception
        timestamp failed_at
    }

    users ||--o{ roles : "has many"
    users ||--o{ media : "uploads"
    users ||--o{ properties : "creates"
    users ||--o{ units : "creates"
    users ||--o{ pages : "creates"
    users ||--o{ posts : "creates"
    users ||--o{ locations : "creates"
    users ||--o{ audit_logs : "actions"
    users ||--o{ bookings : "handled_by"
    
    roles ||--o{ model_has_roles : "assigned to"
    model_has_roles }o-- || users : "belongs to"
    model_has_roles }o-- || roles : "belongs to"
    
    settings ||--o{ : "configures"
    
    media }o--o{ media_folders : "in folder"
    
    properties ||--o{ units : "has many"
    properties }o--|| locations : "in location"
    properties }o--|| amenities : "has many"
    properties ||--o{ bookings : "has bookings"
    properties ||--o{ seo_metadata : "has seo"
    
    units }o--|| amenities : "has many"
    units ||--o{ bookings : "has bookings"
    units ||--o{ seo_metadata : "has seo"
    
    pages ||--o{ page_blocks : "has many"
    pages ||--o{ seo_metadata : "has seo"
    
    posts }o--|| categories : "in category"
    posts }o--|| tags : "has many"
    posts ||--o{ seo_metadata : "has seo"
    
    categories }o--|| categories : "has subcategories"
    
    locations ||--o{ seo_metadata : "has seo"
    
    bookings }o--|| properties : "for property"
    bookings }o--|| units : "for unit"
    
    integrations ||--o{ : "configures"
    
    audit_logs }o--|| users : "by user"
```

## Database Schema Details

### Core Tables

#### `users`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | User's full name |
| `email` | string | Email address (unique) |
| `email_verified_at` | timestamp | Email verification timestamp |
| `password` | string | Hashed password |
| `remember_token` | string | Remember me token |
| `phone` | string | Contact phone number |
| `avatar` | string | Avatar filename |
| `last_login` | timestamp | Last login timestamp |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Indexes:**
- `id` (Primary)
- `email` (Unique)
- `phone`

#### `roles`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Role display name |
| `slug` | string | URL-friendly slug (unique) |
| `description` | text | Role description |
| `permissions` | json | JSON array of permissions |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Permissions Structure:**
```json
{
  "properties": ["view", "create", "update", "delete"],
  "bookings": ["view", "update"],
  "seo": ["edit"],
  "settings": ["edit"]
}
```

#### `settings`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `key` | string | Setting key (unique) |
| `value` | text | Setting value |
| `type` | string | Data type: string, integer, boolean, json |
| `description` | text | Setting description |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**Key Setting Examples:**
```php
'site_name' => 'My Apartment',
'site_tagline' => 'Quality Living in Premium Location',
'site_url' => 'https://example.com',
'timezone' => 'Asia/Jakarta',
'locale' => 'id',
'currency' => 'IDR',
'whatsapp_default' => '6281234567890',
'primary_color' => '#3B82F6',
'secondary_color' => '#10B981',
'accent_color' => '#F59E0B',
```

#### `media`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Uploader user ID |
| `disk` | string | Storage disk (local/s3) |
| `directory` | string | Storage directory |
| `filename` | string | Safe filename |
| `original_filename` | string | Original filename |
| `mime_type` | string | MIME type |
| `extension` | string | File extension |
| `size` | integer | File size in bytes |
| `width` | integer | Image width |
| `height` | integer | Image height |
| `type` | string | Type: image, document, video |
| `alt` | string | Alt text |
| `title` | string | Title |
| `caption` | string | Caption |
| `description` | text | Description |
| `metadata` | json | Additional metadata |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

**File Storage Structure:**
```
storage/app/public/
├── media/
│   ├── 2026/
│   │   ├── 08/
│   │   │   ├── property-1.jpg
│   │   │   ├── unit-studio-deluxe.jpg
│   │   │   └── thumbnails/
│   │   │       ├── property-1-150x150.jpg
│   │   │       ├── property-1-300x300.jpg
│   │   │       └── property-1-768x768.jpg
```

#### `properties`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Creator user ID |
| `location_id` | bigint | Location ID |
| `name` | string | Property name |
| `slug` | string | URL-friendly slug (unique) |
| `short_description` | text | Short summary |
| `description` | text | Full description |
| `address` | string | Street address |
| `city` | string | City name |
| `province` | string | Province name |
| `postal_code` | string | ZIP code |
| `latitude` | float | GPS latitude |
| `longitude` | float | GPS longitude |
| `whatsapp_number` | string | WhatsApp contact |
| `phone` | string | Phone number |
| `email` | string | Email address |
| `amenities` | json | JSON array of amenities |
| `features` | json | JSON array of features |
| `status` | string | draft, published, archived |
| `featured` | boolean | Featured property |
| `sort_order` | integer | Display order |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `units`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `property_id` | bigint | Property ID |
| `user_id` | bigint | Creator user ID |
| `name` | string | Unit name |
| `slug` | string | URL-friendly slug |
| `type` | string | Unit type (Studio, 1BR, 2BR, etc.) |
| `short_description` | text | Short summary |
| `description` | text | Full description |
| `bedrooms` | integer | Number of bedrooms |
| `bathrooms` | integer | Number of bathrooms |
| `max_guests` | integer | Maximum guests |
| `size` | float | Size in m² |
| `price` | decimal(10,2) | Price |
| `price_type` | string | nightly, weekly, monthly |
| `status` | string | draft, available, rented, archived |
| `featured` | boolean | Featured unit |
| `amenities` | json | JSON array of amenities |
| `images` | json | JSON array of image filenames |
| `whatsapp_number` | string | Unit-specific WhatsApp |
| `phone` | string | Unit-specific phone |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `amenities`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Amenity name |
| `slug` | string | URL-friendly slug (unique) |
| `icon` | string | Icon class/emoji |
| `description` | text | Description |
| `color` | string | Display color |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `bookings`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `property_id` | bigint | Property ID |
| `unit_id` | bigint | Unit ID |
| `booking_code` | string | Unique booking code |
| `name` | string | Customer name |
| `phone` | string | Customer phone (WhatsApp) |
| `email` | string | Customer email |
| `check_in` | date | Check-in date |
| `check_out` | date | Check-out date |
| `guests` | integer | Number of guests |
| `notes` | text | Customer notes |
| `status` | string | new, contacted, confirmed, completed, cancelled, spam |
| `landing_page` | string | Landing page URL |
| `utm_source` | string | UTM source |
| `utm_medium` | string | UTM medium |
| `utm_campaign` | string | UTM campaign |
| `utm_term` | string | UTM term |
| `utm_content` | string | UTM content |
| `whatsapp_number` | string | Final WhatsApp number |
| `whatsapp_url` | string | Generated WhatsApp URL |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `seo_metadata`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `model_type` | string | Model class name |
| `model_id` | bigint | Model ID |
| `seo_title` | string | SEO title |
| `seo_description` | string | SEO meta description |
| `canonical_url` | string | Canonical URL |
| `og_title` | string | Open Graph title |
| `og_description` | string | Open Graph description |
| `og_image` | string | Open Graph image URL |
| `twitter_title` | string | Twitter/X title |
| `twitter_description` | string | Twitter/X description |
| `twitter_image` | string | Twitter/X image URL |
| `robots` | string | Robots directive |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `redirects`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `from_url` | string | Source URL (without domain) |
| `to_url` | string | Destination URL |
| `status_code` | integer | 301 or 302 |
| `is_active` | boolean | Redirect active |
| `hit_count` | integer | Number of hits |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `integrations`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Integration name |
| `slug` | string | Integration slug |
| `config` | json | Integration configuration |
| `is_active` | boolean | Integration enabled |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Update timestamp |

#### `audit_logs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | User ID |
| `action` | string | Action performed |
| `model_type` | string | Model class name |
| `model_id` | bigint | Model ID |
| `old_values` | json | Previous values |
| `new_values` | json | New values |
| `ip_address` | string | User IP |
| `user_agent` | string | User agent |
| `created_at` | timestamp | Creation timestamp |

### Pivot Tables

#### `model_has_roles`

| Column | Type | Description |
|--------|------|-------------|
| `role_id` | bigint | Role ID |
| `model_type` | string | Model class |
| `model_id` | bigint | Model ID |

#### `model_has_permissions`

| Column | Type | Description |
|--------|------|-------------|
| `permission_id` | bigint | Permission ID |
| `model_type` | string | Model class |
| `model_id` | bigint | Model ID |

#### `amenity_property`

| Column | Type | Description |
|--------|------|-------------|
| `amenity_id` | bigint | Amenity ID |
| `property_id` | bigint | Property ID |

#### `amenity_unit`

| Column | Type | Description |
|--------|------|-------------|
| `amenity_id` | bigint | Amenity ID |
| `unit_id` | bigint | Unit ID |

#### `post_category`

| Column | Type | Description |
|--------|------|-------------|
| `post_id` | bigint | Post ID |
| `category_id` | bigint | Category ID |

#### `post_tag`

| Column | Type | Description |
|--------|------|-------------|
| `post_id` | bigint | Post ID |
| `tag_id` | bigint | Tag ID |

## Database Configuration

### Laravel `.env` Configuration

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=apartment_cms
DB_USERNAME=root
DB_PASSWORD=
DB_PREFIX=
```

### Migration Commands

```bash
# Run all migrations
php artisan migrate

# Run migrations with fresh database
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:reset
```

### Seeders

```bash
# Run seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=AmenitiesTableSeeder
```

## Database Optimization

### Indexes for Performance

```sql
-- Indexes for frequently queried fields
CREATE INDEX idx_properties_slug ON properties(slug);
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_featured ON properties(featured);

CREATE INDEX idx_units_slug ON units(slug);
CREATE INDEX idx_units_property_id ON units(property_id);
CREATE INDEX idx_units_status ON units(status);

CREATE INDEX idx_bookings_booking_code ON bookings(booking_code);
CREATE INDEX idx_bookings_property_id ON bookings(property_id);
CREATE INDEX idx_bookings_unit_id ON bookings(unit_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_check_in ON bookings(check_in);

CREATE INDEX idx_seo_metadata_model ON seo_metadata(model_type, model_id);

CREATE INDEX idx_redirects_from_url ON redirects(from_url);
CREATE INDEX idx_redirects_is_active ON redirects(is_active);

CREATE INDEX idx_settings_key ON settings(key);
```

### Query Optimization Tips

```php
// Use eager loading to prevent N+1
$properties = Property::with(['units', 'amenities', 'seo'])->get();

// Use whereIn for multiple IDs
$units = Unit::whereIn('property_id', $propertyIds)->get();

// Use pagination for listings
$bookings = Booking::with(['property', 'unit'])->paginate(20);

// Use whereDate for date comparisons
$bookings = Booking::whereDate('check_in', '>=', now())->get();
```

## Database Backup Strategy

### Manual Backup

```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Backup with compression
mysqldump -u username -p database_name | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Automated Backup (cPanel)

1. Login to cPanel
2. Navigate to "Backup"
3. Configure daily/weekly backup
4. Select "Full Backup"
5. Enter email for notifications

### Restore Backup

```bash
# Restore from backup
mysql -u username -p database_name < backup.sql

# Restore from compressed backup
gunzip < backup.sql.gz | mysql -u username -p database_name
```