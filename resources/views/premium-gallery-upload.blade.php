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
                <h3 class="pg-upload-title">Arrastra tus imágenes aquí</h3>
                <p class="pg-upload-subtitle">o haz clic para seleccionar archivos</p>
                <div class="pg-btn-upload">Seleccionar Archivos</div>
                <p class="pg-upload-hint">
                    <span class="pg-hint-item">JPG, PNG, WebP</span>
                    <span class="pg-hint-divider">|</span>
                    <span class="pg-hint-item">Máx. {{ $maxFiles }} imágenes</span>
                    <span class="pg-hint-divider">|</span>
                    <span class="pg-hint-item">Máx. {{ round($getMaxSize() / 1024) }}MB</span>
                </p>
            </div>
        </div>


        {{-- Gallery Grid --}}
        <div class="pg-gallery-grid" x-show="totalCount > 0" style="margin-top: 1.5rem;">
            <template x-for="image in allImages" :key="image.uniqueId">
                <div class="pg-card" :class="{ 'pg-card-primary': image.isPrimary }">
                    <div class="pg-card-preview">
                        <img :src="image.thumbnail || image.url" class="pg-card-image">

                        <div class="pg-primary-badge" x-show="image.isPrimary">★ Principal</div>

                        <div class="pg-loading" x-show="image.uploading">
                            <div class="pg-spinner"></div>
                        </div>

                        <div class="pg-card-actions" x-show="!image.uploading">
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

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('premiumGallery', ({ statePath, maxFiles, initialImages }) => ({
                    statePath, // Store statePath for use in uploadMultiple
                    maxFiles,
                    savedImages: initialImages.map(img => ({ ...img, type: 'saved', uniqueId: 'saved-' + img.id, uploading: false })),
                    newImages: [],
                    dragActive: false,
                    lightboxOpen: false,
                    lightboxImage: null,
                    uploading: false, // Add global uploading state

                    get allImages() {
                        return [...this.savedImages, ...this.newImages];
                    },

                    get totalCount() {
                        return this.allImages.length;
                    },

                    handleDrop(e) {
                        this.dragActive = false;
                        const files = e.dataTransfer.files;
                        this.$refs.fileInput.files = files;
                        this.$refs.fileInput.dispatchEvent(new Event('change'));
                    },

                    handleFileSelect(e) {
                        let files = Array.from(e.target.files);

                        // Strict Validation
                        const remainingSlots = this.maxFiles - this.totalCount;

                        if (files.length > remainingSlots) {
                            alert(`Solo puedes subir ${remainingSlots} imagen(es) más.`);
                            files = files.slice(0, remainingSlots);

                            // Visual update for input
                            const dataTransfer = new DataTransfer();
                            files.forEach(file => dataTransfer.items.add(file));
                            this.$refs.fileInput.files = dataTransfer.files;
                        }

                        if (files.length === 0) return;

                        this.uploading = true;

                        // Create local previews
                        files.forEach(file => {
                            if (!file.type.startsWith('image/')) return;

                            const tempId = Math.random().toString(36).substr(2, 9);
                            const url = URL.createObjectURL(file);

                            this.newImages.push({
                                uniqueId: 'new-' + tempId,
                                id: null,
                                name: file.name,
                                url: url,
                                thumbnail: url,
                                isPrimary: false,
                                uploading: true,
                                type: 'new'
                            });
                        });

                        // Manual Upload Control
                        this.$wire.uploadMultiple(
                            this.statePath,
                            files,
                            () => {
                                // Success
                                this.markUploadsFinished();
                            },
                            () => {
                                // Error
                                this.uploading = false;
                                alert('Error al subir las imágenes.');
                                // Remove failed uploads from UI
                                this.newImages = this.newImages.filter(img => img.type !== 'new' || !img.uploading);
                            },
                            (event) => {
                                // Progress (optional)
                            }
                        );
                    },

                    markUploadsFinished() {
                        this.uploading = false;
                        // Mark all "new" images that are currently uploading as finished
                        this.newImages.forEach(img => {
                            if (img.uploading) {
                                img.uploading = false;
                            }
                        });

                        // Optional: Trigger a save or refresh if needed, 
                        // but usually we wait for user to save the form.
                    },

                    deleteImage(image) {
                        if (image.type === 'saved') {
                            if (confirm('¿Eliminar?')) {
                                // Delete from server
                                fetch(`/api/media/${image.id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json'
                                    }
                                }).then(() => {
                                    // Remove from UI
                                    this.savedImages = this.savedImages.filter(i => i.id !== image.id);
                                }).catch(err => {
                                    console.error('Error deleting image:', err);
                                    alert('Error al eliminar la imagen');
                                });
                            }
                        } else {
                            // Clear from input (hard to do selectively, so we might have to clear all new)
                            // This uses wire:model, so removing from UI doesn't remove from upload queue easily without refreshing.
                            // For this simplified version, we just hide it.
                            this.newImages = this.newImages.filter(i => i.uniqueId !== image.uniqueId);
                        }
                    },

                    setPrimary(image) {
                        if (image.type !== 'saved') return;
                        if (image.isPrimary) return;

                        // Optimistic update
                        const oldPrimary = this.savedImages.find(i => i.isPrimary);
                        if (oldPrimary) oldPrimary.isPrimary = false;

                        const newPrimary = this.savedImages.find(i => i.id === image.id);
                        if (newPrimary) newPrimary.isPrimary = true;

                        fetch(`/api/media/${image.id}/set-primary`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        }).catch(err => {
                            console.error('Error setting primary:', err);
                            alert('Error al establecer imagen principal');
                            // Revert on error
                            if (newPrimary) newPrimary.isPrimary = false;
                            if (oldPrimary) oldPrimary.isPrimary = true;
                        });
                    },



                    viewImage(image) {
                        this.lightboxImage = image;
                        this.lightboxOpen = true;
                    },

                    nextImage() {
                        const currentIndex = this.allImages.findIndex(img => img.uniqueId === this.lightboxImage.uniqueId);
                        const nextIndex = (currentIndex + 1) % this.allImages.length;
                        this.lightboxImage = this.allImages[nextIndex];
                    },

                    prevImage() {
                        const currentIndex = this.allImages.findIndex(img => img.uniqueId === this.lightboxImage.uniqueId);
                        const prevIndex = (currentIndex - 1 + this.allImages.length) % this.allImages.length;
                        this.lightboxImage = this.allImages[prevIndex];
                    },

                    closeLightbox() {
                        this.lightboxOpen = false;
                        this.lightboxImage = null;
                    }
                }));
            });
        </script>
    @endpush
</x-dynamic-component>