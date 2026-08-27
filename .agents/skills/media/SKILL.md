---
name: media
description: >-
  Use when working on media/uploads: the media library, uploading or importing
  files, property photos and galleries, featured images, file validation, or
  storage paths. Trigger phrases: "upload a file", "media library", "property
  photos", "featured image", "import from URL", "file validation", "thumbnail",
  "storage disk". Grounds work in the real Media model, upload helper, and the
  public disk convention.
---

# Purpose
Media is stored on the `public` disk with a specific naming/folder convention and
a shared upload helper. This skill stops agents from switching disks, inventing a
parallel storage layout, weakening upload validation, or breaking the SSRF-guarded
URL import and the SVG stored-XSS awareness.

# When to Use
- Any upload path (media library, property photos, blog image upload).
- Changing file validation, storage location, filenames, or thumbnails.
- Touching the featured-image relationship or the URL-import feature.

# Rules
- Storage is the `public` disk, symlinked to `storage/app/public` via
  `php artisan storage:link`. Do not switch disks or invent new paths without an
  impact analysis — URLs and existing records depend on the current layout.
- Route uploads through the `upload_file()` helper
  ([`app/Helpers/upload.php`](app/Helpers/upload.php)). It slugifies folders,
  derives the extension from the MIME type (not the client filename), and produces
  `{prefix}_{category}_{DDMMYYYY}_{random8}.{ext}`. Do not build filenames by hand
  or trust `getClientOriginalExtension()` for security decisions.
- The `Media` model ([`app/Models/Media.php`](app/Models/Media.php)) stores `disk`,
  `directory`, `filename`, `mime_type`, etc.; `url`/`thumbnail_url` are accessors
  and `deleteFile()` removes the file + thumbnail. Use `deleteFile()` when deleting
  a media record — do not orphan files.
- Validate every upload via [`MediaRequest`](app/Http/Requests/MediaRequest.php):
  `max:10240` (10MB) and `mimes:jpg,jpeg,png,webp,gif,svg,pdf,doc,docx,mp4,avi,mov`.
  Do not silently widen the allowlist.
- SVG upload is allowed and is a known stored-XSS risk — treat uploaded SVGs as
  untrusted HTML. Do not add code that renders SVG inline from untrusted uploads;
  do not expand SVG acceptance to more surfaces without sanitization/restriction.
- The media admin supports importing from a URL
  ([`MediaController::fromUrl`](app/Http/Controllers/MediaController.php),
  route `admin.media.from-url`). It has SSRF protection covered by
  [`tests/Feature/MediaUrlImportSsrfTest.php`](tests/Feature/MediaUrlImportSsrfTest.php).
  Keep those protections intact — never fetch arbitrary user-supplied URLs unchecked.
- Property galleries use [`PropertyPhoto`](app/Models/PropertyPhoto.php)
  (`property_id` FK, `media_id`, `category`, `sort_order`). The featured image is
  separate: `properties.featured_image_id` → `Property::featuredImage()` (a `Media`).
  Do not conflate the two, and check which media is the featured image before
  reordering or deleting.

# Workflow
1. Read [`app/Helpers/upload.php`](app/Helpers/upload.php) and
   [`app/Models/Media.php`](app/Models/Media.php) before changing any upload path.
2. Add/adjust validation in [`MediaRequest`](app/Http/Requests/MediaRequest.php);
   keep MIME + size limits.
3. For deletes, call `Media::deleteFile()` so the file and thumbnail are removed.
4. For galleries, go through `PropertyPhoto`; for the featured image, set
   `featured_image_id`.
5. If touching URL import, re-run the SSRF test.

# Common Mistakes
- Switching from the `public` disk or inventing a new folder scheme.
- Building filenames manually / trusting the client extension.
- Widening the `mimes` allowlist or rendering untrusted SVGs inline.
- Deleting a `Media` row without `deleteFile()` (orphaned files).
- Breaking the `featured_image_id` relationship when reordering photos.

# Validation
- `php artisan test --filter=MediaUrlImportSsrfTest` passes (SSRF intact).
- `php artisan storage:link` exists / public URLs resolve.
- Confirm uploads still go through `upload_file()` and `MediaRequest` validation.

# Related Files
- [`app/Models/Media.php`](app/Models/Media.php), [`app/Models/PropertyPhoto.php`](app/Models/PropertyPhoto.php)
- [`app/Helpers/upload.php`](app/Helpers/upload.php), [`app/Http/Requests/MediaRequest.php`](app/Http/Requests/MediaRequest.php)
- [`app/Http/Controllers/MediaController.php`](app/Http/Controllers/MediaController.php)
- [`config/filesystems.php`](config/filesystems.php), [`tests/Feature/MediaUrlImportSsrfTest.php`](tests/Feature/MediaUrlImportSsrfTest.php)
