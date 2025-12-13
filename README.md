# Premium Gallery Plugin for Filament

A powerful, customizable, and user-friendly image gallery component for Filament PHP 4.x/3.x.

## Features

- 🖼️ **Premium UI**: Modern grid layout using standard Filament design tokens.
- ⬆️ **Manual Upload Control**: Prevents auto-uploading, allowing users to validate files before sending.
- ⭐ **Set Primary Image**: Mark any image as the main/cover photo with a single click.
- 👁️ **Lightbox Preview**: Built-in full-screen image viewer.
- 📱 **Responsive**: Perfect layout on desktop, tablet, and mobile.
- ⚡ **Optimized**: Supports image drag-and-drop and reordering.

## Requirements

- PHP 8.2+
- Filament 4.x or 3.x
- Laravel 11/12

## Installation

Since this is a private/local package, you need to install it via your `composer.json`.

### 1. Add Repository
Add the repository to your root `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/danielriveraayala/premium-gallery-plugin.git"
    }
]
```

### 2. Require Package
Run the following command:

```bash
composer require inmoflow/premium-gallery
```

## Usage

Use the component in your Filament Resource forms (`Form $form`):

```php
use Inmoflow\PremiumGallery\Forms\Components\PremiumGalleryUpload;

PremiumGalleryUpload::make('gallery')
    ->label('Property Photos')
    ->collection('gallery') // Optional: Spatie Media Library collection
    ->maxFiles(10)
    ->maxSize(5120) // 5MB
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->columnSpanFull();
```

### Handling "Primary" Image

The component automatically handles the `is_primary` custom property on Spatie Media Library items. To use it in your frontend/API:

```php
// Get the primary image
$primaryImage = $record->getMedia('gallery')->firstWhere('custom_properties.is_primary', true);

// Fallback to first image if no primary set
$cover = $primaryImage ?? $record->getFirstMedia('gallery');
```

## Security

This plugin enforces:
- Server-side strict file validation.
- Authorization checks on media deletion (ensure your Policies allow it).

## Credits

- InmoFlow Team
- Built with [Filament](https://filamentphp.com)

## License

The MIT License (MIT).
