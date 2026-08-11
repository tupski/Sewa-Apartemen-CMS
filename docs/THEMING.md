# Theme System

## Overview

The theme system allows visual customization of the Apartment Rental CMS without modifying code. Themes control colors, typography, spacing, and layout through database-driven CSS variables and reusable Blade components.

## Theme Architecture

### Theme System Components

```
resources/
├── views/
│   ├── components/
│   │   ├── header.blade.php
│   │   ├── footer.blade.php
│   │   ├── hero.blade.php
│   │   ├── property-card.blade.php
│   │   └── ...
│   ├── layouts/
│   │   ├── app.blade.php
│   │   └── admin.blade.php
│   └── themes/
│       └── modern/
│           ├── header.blade.php
│           ├── footer.blade.php
│           └── ...
└── assets/
    └── css/
        └── themes/
            └── modern.css
```

### Theme Configuration

Themes are stored in the database with these settings:

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `active_theme` | string | modern | Active theme name |
| `theme_primary_color` | string | #3B82F6 | Primary color |
| `theme_secondary_color` | string | #10B981 | Secondary color |
| `theme_accent_color` | string | #F59E0B | Accent color |
| `theme_font_family` | string | Inter | Font family |
| `theme_spacing_unit` | integer | 8 | Base spacing unit (px) |
| `theme_border_radius` | string | 8px | Border radius |
| `theme_button_style` | string | modern | Button style: modern, outline, ghost |
| `theme_container_width` | string | 1200px | Max container width |
| `theme_spacing_top` | integer | 64 | Top spacing (px) |
| `theme_spacing_bottom` | integer | 64 | Bottom spacing (px) |

## Theme Types

### 1. Modern Theme (Default)

Clean, professional design with:

- Minimalist layout
- Modern colors
- Smooth transitions
- Responsive design
- Professional typography

**Color Palette:**
- Primary: #3B82F6 (Blue)
- Secondary: #10B981 (Green)
- Accent: #F59E0B (Orange)
- Text: #1F2937 (Dark)
- Background: #F9FAFB (Light)

### 2. Luxury Theme (Future)

Elegant, high-end design with:

- Dark mode support
- Gold/white color scheme
- Sophisticated typography
- Minimalist imagery
- Premium feel

**Color Palette:**
- Primary: #D4AF37 (Gold)
- Secondary: #1A1A1A (Dark)
- Accent: #F5F5F5 (White)
- Text: #FFFFFF (White)
- Background: #1A1A1A (Dark)

### 3. Minimal Theme (Future)

Ultra-minimalist design with:

- Monochrome colors
- Ample white space
- Clean typography
- Flat design
- Fast loading

**Color Palette:**
- Primary: #000000 (Black)
- Secondary: #666666 (Gray)
- Accent: #FFFFFF (White)
- Text: #000000 (Black)
- Background: #FFFFFF (White)

### 4. Elegant Theme (Future)

Graceful, sophisticated design with:

- Pastel colors
- Serif typography
- Soft shadows
- Classic styling
- Timeless design

**Color Palette:**
- Primary: #8E44AD (Purple)
- Secondary: #F1C40F (Gold)
- Accent: #3498DB (Blue)
- Text: #2C3E50 (Dark Blue)
- Background: #FCFCFC (Off-white)

## CSS Variables (Design Tokens)

### Color Variables

```css
:root {
    /* Primary Colors */
    --color-primary: #3B82F6;
    --color-primary-dark: #2563EB;
    --color-primary-light: #60A5FA;
    
    /* Secondary Colors */
    --color-secondary: #10B981;
    --color-secondary-dark: #059669;
    --color-secondary-light: #34D399;
    
    /* Accent Colors */
    --color-accent: #F59E0B;
    --color-accent-dark: #D97706;
    --color-accent-light: #FBBF24;
    
    /* Neutral Colors */
    --color-text: #1F2937;
    --color-text-light: #4B5563;
    --color-text-lighter: #6B7280;
    --color-bg: #F9FAFB;
    --color-bg-light: #FFFFFF;
    --color-border: #E5E7EB;
    --color-border-light: #F3F4F6;
}
```

### Typography Variables

```css
:root {
    /* Font Family */
    --font-family: Inter, system-ui, sans-serif;
    
    /* Font Sizes */
    --font-size-xs: 0.75rem;
    --font-size-sm: 0.875rem;
    --font-size-base: 1rem;
    --font-size-lg: 1.125rem;
    --font-size-xl: 1.25rem;
    --font-size-2xl: 1.5rem;
    --font-size-3xl: 1.875rem;
    --font-size-4xl: 2.25rem;
    --font-size-5xl: 3rem;
    
    /* Font Weights */
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    
    /* Line Heights */
    --line-height-tight: 1.25;
    --line-height-normal: 1.5;
    --line-height-relaxed: 1.75;
    
    /* Letter Spacing */
    --letter-spacing-tight: -0.025em;
    --letter-spacing-normal: 0;
    --letter-spacing-wide: 0.05em;
}
```

### Spacing Variables

```css
:root {
    /* Base Spacing */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-base: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    --spacing-2xl: 3rem;
    --spacing-3xl: 4rem;
    --spacing-4xl: 6rem;
    
    /* Container */
    --container-width: 1200px;
    --container-padding: 1.5rem;
    
    /* Layout Spacing */
    --spacing-section-top: 4rem;
    --spacing-section-bottom: 4rem;
}
```

### Border & Radius Variables

```css
:root {
    /* Border Widths */
    --border-width: 1px;
    --border-width-sm: 0.5px;
    --border-width-lg: 2px;
    
    /* Border Radius */
    --radius-sm: 4px;
    --radius-base: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --radius-full: 9999px;
    
    /* Shadows */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}
```

## Theme Configuration Admin Panel

### Theme Settings Form

```html
<form method="POST" action="/admin/settings/theme">
    @csrf
    
    <!-- Theme Selection -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Active Theme</label>
        <div class="mt-2 grid grid-cols-2 gap-4">
            <label class="flex items-center p-4 border rounded cursor-pointer hover:border-blue-500">
                <input type="radio" name="active_theme" value="modern" checked>
                <span class="ml-2">Modern</span>
            </label>
            <label class="flex items-center p-4 border rounded cursor-pointer hover:border-blue-500">
                <input type="radio" name="active_theme" value="luxury">
                <span class="ml-2">Luxury</span>
            </label>
        </div>
    </div>
    
    <!-- Primary Color -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Primary Color</label>
        <div class="mt-2 flex items-center space-x-3">
            <input type="color" name="theme_primary_color" value="#3B82F6" class="w-12 h-10 rounded">
            <input type="text" name="theme_primary_color" value="#3B82F6" class="form-input">
            <span class="text-sm text-gray-500">Primary brand color</span>
        </div>
    </div>
    
    <!-- Secondary Color -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Secondary Color</label>
        <div class="mt-2 flex items-center space-x-3">
            <input type="color" name="theme_secondary_color" value="#10B981" class="w-12 h-10 rounded">
            <input type="text" name="theme_secondary_color" value="#10B981" class="form-input">
        </div>
    </div>
    
    <!-- Accent Color -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Accent Color</label>
        <div class="mt-2 flex items-center space-x-3">
            <input type="color" name="theme_accent_color" value="#F59E0B" class="w-12 h-10 rounded">
            <input type="text" name="theme_accent_color" value="#F59E0B" class="form-input">
        </div>
    </div>
    
    <!-- Font Family -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Font Family</label>
        <select name="theme_font_family" class="mt-2 form-select">
            <option value="Inter">Inter</option>
            <option value="Roboto">Roboto</option>
            <option value="Open Sans">Open Sans</option>
            <option value="Lato">Lato</option>
            <option value="Poppins">Poppins</option>
            <option value="Nunito">Nunito</option>
        </select>
    </div>
    
    <!-- Button Style -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Button Style</label>
        <select name="theme_button_style" class="mt-2 form-select">
            <option value="modern">Modern (Filled)</option>
            <option value="outline">Outline</option>
            <option value="ghost">Ghost</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Save Theme Settings</button>
</form>
```

### Theme Settings Controller

```php
class ThemeSettingsController extends Controller
{
    public function edit()
    {
        $settings = SettingsService::all();
        return view('admin.theme.edit', compact('settings'));
    }
    
    public function update(Request $request)
    {
        $validated = $request->validate([
            'active_theme' => 'required|in:modern,luxury,minimal,elegant',
            'theme_primary_color' => 'required|color_code',
            'theme_secondary_color' => 'required|color_code',
            'theme_accent_color' => 'required|color_code',
            'theme_font_family' => 'required|string',
            'theme_button_style' => 'required|in:modern,outline,ghost',
            'theme_container_width' => 'required|regex:/^\d+(px|%)$/','
        ]);
        
        foreach ($validated as $key => $value) {
            Settings::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        
        // Clear theme cache
        Cache::forget('theme');
        
        return back()->with('success', 'Theme settings saved successfully.');
    }
}
```

## Blade Component Theming

### Dynamic Component Classes

```php
// resources/views/components/header.blade.php
@php
    $primaryColor = settings('theme_primary_color', '#3B82F6');
    $fontFamily = settings('theme_font_family', 'Inter');
    $buttonStyle = settings('theme_button_style', 'modern');
@endphp

<style>
    :root {
        --color-primary: {{ $primaryColor }};
        --font-family: {{ $fontFamily }}, system-ui, sans-serif;
    }
    
    .btn-primary {
        background-color: var(--color-primary);
    }
    
    .btn-primary:hover {
        background-color: var(--color-primary-dark);
    }
</style>

<header class="header">
    <nav class="nav">
        <a href="/" class="logo">
            <img src="{{ settings('logo') }}" alt="{{ settings('site_name') }}">
        </a>
        
        <div class="nav-links">
            <a href="/apartments">Properties</a>
            <a href="/locations">Locations</a>
            <a href="/blog">Blog</a>
            <a href="/contact">Contact</a>
        </div>
        
        <a href="/admin" class="btn btn-primary">Admin</a>
    </nav>
</header>
```

### Button Component

```php
// resources/views/components/button.blade.php
@props([
    'variant' => 'primary',
    'size' => 'md',
    'fullWidth' => false,
])

@php
    $buttonStyle = settings('theme_button_style', 'modern');
    
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'bg-green-600 text-white hover:bg-green-700',
        'outline' => 'border-2 border-gray-300 text-gray-700 hover:bg-gray-50',
        'ghost' => 'text-gray-700 hover:bg-gray-100',
        'link' => 'text-blue-600 hover:underline',
    ];
    
    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];
@endphp

<button {{ $attributes->merge(['class' => "btn btn-{$variant} btn-{$size}"]) }}>
    {{ $slot }}
</button>
```

### Card Component

```php
// resources/views/components/card.blade.php
@props([
    'title',
    'subtitle',
])

@php
    $borderRadius = settings('theme_border_radius', '8px');
@endphp

<div {{ $attributes->merge(['class' => "card rounded-{$borderRadius} shadow-sm"]) }}>
    @if($title)
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            @if($subtitle)
                <p class="card-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="card-body">
        {{ $slot }}
    </div>
    
    @if($actions)
        <div class="card-footer">
            {{ $actions }}
        </div>
    @endif
</div>
```

## Theme Customization

### Customizing Colors

```css
/* Custom colors */
:root {
    --color-primary: #2563EB;
    --color-secondary: #059669;
    --color-accent: #D97706;
}

/* Light variants */
:root {
    --color-primary-light: #60A5FA;
    --color-secondary-light: #34D399;
    --color-accent-light: #FBBF24;
}

/* Dark variants */
:root {
    --color-primary-dark: #1E40AF;
    --color-secondary-dark: #047857;
    --color-accent-dark: #B45309;
}
```

### Customizing Typography

```css
/* Font family */
:root {
    --font-family: 'Inter', system-ui, sans-serif;
}

/* Font sizes */
:root {
    --font-size-base: 1rem;
    --font-size-lg: 1.125rem;
    --font-size-xl: 1.25rem;
}

/* Font weights */
:root {
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
}
```

### Customizing Spacing

```css
/* Base spacing */
:root {
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-base: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
}

/* Container width */
:root {
    --container-width: 1200px;
}

/* Section spacing */
:root {
    --spacing-section-top: 4rem;
    --spacing-section-bottom: 4rem;
}
```

## Theme Management

### Creating a New Theme

1. Create theme folder:
```
resources/views/themes/custom-theme/
```

2. Create theme files:
```
resources/views/themes/custom-theme/
├── header.blade.php
├── footer.blade.php
├── hero.blade.php
├── property-card.blade.php
├── unit-card.blade.php
└── booking-form.blade.php
```

3. Register theme in database:
```php
Settings::updateOrCreate(['key' => 'active_theme'], ['value' => 'custom-theme']);
```

4. Clear cache:
```bash
php artisan cache:clear
php artisan view:clear
```

### Theme Structure

```
themes/
└── {theme-name}/
    ├── header.blade.php
    ├── footer.blade.php
    ├── hero.blade.php
    ├── property-card.blade.php
    ├── unit-card.blade.php
    ├── amenities.blade.php
    ├── gallery.blade.php
    ├── booking-form.blade.php
    └── ...
```

### Theme Inheritance

```php
// Base layout
@extends('layouts.app')

// Theme layout
@extends('themes.modern.layout')

// Component
@component('themes.modern.property-card', ['property' => $property])
@endcomponent
```

## Advanced Theme Features

### Dark Mode Support

```css
/* Dark mode support */
[data-theme="dark"] {
    --color-bg: #1F2937;
    --color-bg-light: #374151;
    --color-text: #F9FAFB;
    --color-text-light: #D1D5DB;
    --color-border: #374151;
}

/* Dark mode toggle */
<button id="theme-toggle">
    <svg class="sun-icon" />
    <svg class="moon-icon" />
</button>

<script>
    document.getElementById('theme-toggle').addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
</script>
```

### Custom Fonts

```css
/* Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* Font family */
:root {
    --font-family: 'Inter', system-ui, sans-serif;
}
```

### Custom Animations

```css
/* Custom animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

.animate-slide-in {
    animation: slideIn 0.5s ease-out;
}
```

## Performance Optimization

### CSS Minification

```bash
# Production build
npm run build

# Vite configuration
export default defineConfig({
    build: {
        cssMinify: true,
    },
});
```

### Critical CSS

```php
// Inline critical CSS
<style>
    {{ file_get_contents(public_path('css/critical.css')) }}
</style>
```

### Font Optimization

```html
<!-- Font preload -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">

<!-- Font swap -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap&font-display=swap">
```

## Theme Testing

### Test Theme Changes

```bash
# Test theme in local environment
php artisan serve

# Clear cache
php artisan cache:clear
php artisan view:clear
```

### Visual Regression Testing

1. Take screenshot before changes
2. Make theme changes
3. Take screenshot after changes
4. Compare screenshots
5. Verify consistency

## Conclusion

The theme system provides:

- ✓ Color customization
- ✓ Typography control
- ✓ Spacing customization
- ✓ Reusable components
- ✓ Multiple theme options
- ✓ Dynamic CSS variables
- ✓ Admin panel integration
- ✓ No code changes required

Themes can be created and customized without modifying core code, making the CMS highly flexible for different client needs.