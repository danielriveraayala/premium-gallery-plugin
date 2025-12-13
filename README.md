# Premium Gallery Plugin para Filament

Un componente de galería de imágenes potente, personalizable y fácil de usar para Filament PHP 4.x/3.x.

## Características

- 🖼️ **Interfaz Premium**: Diseño de cuadrícula moderno usando los tokens de diseño estándar de Filament.
- ⬆️ **Control Manual de Carga**: Evita la subida automática, permitiendo validar archivos antes de enviar.
- ⭐ **Establecer Imagen Principal**: Marca cualquier imagen como foto de portada con un solo clic.
- 👁️ **Vista Previa en Lightbox**: Visor de imágenes a pantalla completa integrado.
- 📱 **Responsive**: Diseño perfecto en escritorio, tablet y móvil.
- ⚡ **Optimizado**: Soporta arrastrar y soltar (drag-and-drop) y reordenamiento.

## Requisitos

- PHP 8.2+
- Filament 4.x o 3.x
- Laravel 11/12

## Instalación

Dado que este es un paquete privado/local, necesitas instalarlo a través de tu `composer.json`.

### 1. Agregar Repositorio
Añade el repositorio a tu `composer.json` raíz:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/danielriveraayala/premium-gallery-plugin.git"
    }
]
```

### 2. Requerir el Paquete
Ejecuta el siguiente comando:

```bash
composer require inmoflow/premium-gallery
```

## Uso

Usa el componente en tus formularios de Recursos de Filament (`Form $form`):

```php
use Inmoflow\PremiumGallery\Forms\Components\PremiumGalleryUpload;

PremiumGalleryUpload::make('gallery')
    ->label('Fotos de la Propiedad')
    ->collection('gallery') // Opcional: Colección de Spatie Media Library
    ->maxFiles(10)
    ->maxSize(5120) // 5MB
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->columnSpanFull();
```

### Manejo de Imagen "Principal"

El componente maneja automáticamente la propiedad personalizada `is_primary` en los items de Spatie Media Library. Para usarlo en tu frontend/API:

```php
// Obtener la imagen principal
$primaryImage = $record->getMedia('gallery')->firstWhere('custom_properties.is_primary', true);

// Fallback a la primera imagen si no hay principal
$cover = $primaryImage ?? $record->getFirstMedia('gallery');
```

## Seguridad

Este plugin implementa:
- Validación estricta de archivos en el lado del servidor.
- Verificaciones de autorización al eliminar medios (asegúrate de que tus Policies lo permitan).

## Créditos

- Hecho por [Dany Rivera Mkt](https://about.me/danielriveraayala)
- CEO de [Kreativos Pro](https://kreativos.pro/)
- Construido con [Filament](https://filamentphp.com)

## Licencia

La Licencia MIT (MIT).
