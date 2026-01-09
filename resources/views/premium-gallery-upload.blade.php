@php
    $statePath = $getStatePath();
    $maxFiles = $getMaxFiles();
    $collection = $getCollectionName();
    $acceptedTypes = implode(',', $getAcceptedFileTypes());
    // Get existing media safely
    $existingMedia = $getExistingMedia();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="premiumGallery({
            statePath: '{{ $statePath }}',
            maxFiles: {{ $maxFiles }},
            initialImages: {{ Js::from($existingMedia) }}
         })" class="pg-wrapper" wire:ignore.self>

        {{-- NATIVE LIVEWIRE INPUT (Hidden) --}}
        {{-- This handles the actual upload and state sync safely --}}
        <input type="file" x-ref="fileInput" multiple accept="{{ $acceptedTypes }}" class="hidden"
            style="display: none;" x-on:change="handleFileSelect($event)" x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="markUploadsFinished()"
            x-on:livewire-upload-error="uploading = false; alert('Error iniciando carga');">

        {{-- Upload Zone --}}
        <div class="pg-upload-zone" x-show="totalCount < maxFiles" x-on:click="$refs.fileInput.click()"
            x-on:dragover.prevent="dragActive = true" x-on:dragleave.prevent="dragActive = false"
            x-on:drop.prevent="handleDrop($event)" :class="{ 'pg-drag-active': dragActive }">

            <div class="pg-upload-content">
                <svg class="pg-upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <h3 class="pg-upload-title">Arrastra tus imágenes aquí o haz clic</h3>

                {{-- Compact Hint --}}
                <p class="pg-upload-hint">
                    <span class="pg-hint-item">Imágenes (JPG, PNG, WebP)</span>
                    <span class="pg-hint-divider">|</span>
                    <span class="pg-hint-item">Máx. {{ $maxFiles }}</span>
                </p>
            </div>
        </div>


        {{-- Gallery Grid --}}
        <div class="pg-gallery-grid" x-show="totalCount > 0" style="margin-top: 1.5rem;">
            <template x-for="(image, index) in allImages" :key="image.uniqueId">
                <div class="pg-card"
                    :class="{ 'pg-card-primary': image.isPrimary, 'pg-card-dragging': galleryDraggingIndex === index }"
                    @dragenter="dragEnter($event, index)" @dragover="dragOver($event)" @drop="drop($event, index)">
                    <div class="pg-card-preview">
                        {{-- PRIMARY BADGE --}}
                        <div class="pg-primary-badge" x-show="image.isPrimary">
                            <x-heroicon-s-star class="pg-star-icon" />
                            <span>Principal</span>
                        </div>
                        <img :src="image.thumbnail || image.url" class="pg-card-image">


                        <div class="pg-loading" x-show="image.uploading">
                            <div class="pg-spinner"></div>
                        </div>

                        <div class="pg-card-actions" x-show="!image.uploading">
                            {{-- Drag Handle (Now the only draggable element) --}}
                            <div class="pg-action-btn pg-drag-handle" style="cursor: grab;" draggable="true"
                                @dragstart="dragStart($event, index)" @dragend="dragEnd()">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                </svg>
                            </div>

                            <button type="button" class="pg-action-btn" x-on:click="viewImage(image)">👁️</button>
                            <button type="button" class="pg-action-btn" x-show="image.type === 'saved'"
                                x-on:click="setPrimary(image)"
                                :title="image.isPrimary ? 'Ya es principal' : 'Marcar como principal'"
                                :style="image.isPrimary ? 'background: gold; color: black;' : ''">
                                ★
                            </button>
                            <button type="button" class="pg-action-btn" x-on:click="deleteImage(image)">🗑️</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Lightbox Modal --}}
        <div x-show="lightboxOpen" x-cloak @keydown.escape.window="closeLightbox()"
            @keydown.arrow-left.window="prevImage()" @keydown.arrow-right.window="nextImage()"
            class="pg-lightbox-overlay" x-on:click="closeLightbox()">

            <div class="pg-lightbox-content" x-on:click.stop>
                {{-- Close Button --}}
                <button type="button" class="pg-lightbox-close" x-on:click="closeLightbox()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Previous Button --}}
                <button type="button" class="pg-lightbox-prev" x-on:click="prevImage()" x-show="allImages.length > 1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                {{-- Image --}}
                <img :src="lightboxImage?.url" :alt="lightboxImage?.name" class="pg-lightbox-image">

                {{-- Next Button --}}
                <button type="button" class="pg-lightbox-next" x-on:click="nextImage()" x-show="allImages.length > 1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                {{-- Image Info --}}
                <div class="pg-lightbox-info">
                    <span x-text="lightboxImage?.name"></span>
                </div>
            </div>
        </div>

    </div>

</x-dynamic-component>