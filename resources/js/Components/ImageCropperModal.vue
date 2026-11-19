<script setup>
import Modal from '@/Components/Modal.vue';
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { RotateCcw, Check, X } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    imageFile: { type: [File, Object], default: null },
    aspectRatio: { type: Number, default: null }, // null = free-form box, or pass e.g. 16/5 or 1
    outputMaxWidth: { type: Number, default: 1200 },
    title: { type: String, default: 'Crop Photo' },
});

const emit = defineEmits(['close', 'cropped']);

const imageSrc = ref('');
const naturalWidth = ref(0);
const naturalHeight = ref(0);
const displayWidth = ref(0);
const displayHeight = ref(0);

// Crop box coordinates in displayed image pixels
const box = ref({ x: 0, y: 0, width: 100, height: 100 });

const dragState = ref({
    active: false,
    mode: null, // 'move' | 'nw' | 'ne' | 'sw' | 'se' | 'n' | 's' | 'e' | 'w'
    startX: 0,
    startY: 0,
    startBox: { x: 0, y: 0, width: 0, height: 0 },
});

const MIN_SIZE = 40;

const initDefaultBox = () => {
    const w = displayWidth.value;
    const h = displayHeight.value;
    if (!w || !h) return;

    if (props.aspectRatio) {
        let boxW = w * 0.85;
        let boxH = boxW / props.aspectRatio;
        if (boxH > h * 0.85) {
            boxH = h * 0.85;
            boxW = boxH * props.aspectRatio;
        }
        box.value = {
            x: Math.round((w - boxW) / 2),
            y: Math.round((h - boxH) / 2),
            width: Math.round(boxW),
            height: Math.round(boxH),
        };
    } else {
        const marginX = Math.round(w * 0.1);
        const marginY = Math.round(h * 0.1);
        box.value = {
            x: marginX,
            y: marginY,
            width: w - marginX * 2,
            height: h - marginY * 2,
        };
    }
};

watch(
    () => props.imageFile,
    (file) => {
        if (imageSrc.value) {
            URL.revokeObjectURL(imageSrc.value);
            imageSrc.value = '';
        }
        if (!file) return;

        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            naturalWidth.value = img.naturalWidth;
            naturalHeight.value = img.naturalHeight;

            // Fit entire image cleanly inside modal bounds (max 480x360)
            const maxW = 480;
            const maxH = 360;
            const scale = Math.min(maxW / img.naturalWidth, maxH / img.naturalHeight, 1);

            displayWidth.value = Math.round(img.naturalWidth * scale);
            displayHeight.value = Math.round(img.naturalHeight * scale);
            imageSrc.value = url;

            initDefaultBox();
        };
        img.src = url;
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (imageSrc.value) URL.revokeObjectURL(imageSrc.value);
});

const getClientCoords = (e) => {
    if (e.touches && e.touches.length > 0) {
        return { clientX: e.touches[0].clientX, clientY: e.touches[0].clientY };
    }
    return { clientX: e.clientX, clientY: e.clientY };
};

const startInteraction = (mode, e) => {
    e.preventDefault();
    e.stopPropagation();
    const { clientX, clientY } = getClientCoords(e);
    dragState.value = {
        active: true,
        mode,
        startX: clientX,
        startY: clientY,
        startBox: { ...box.value },
    };
};

const onMove = (e) => {
    if (!dragState.value.active) return;
    const { clientX, clientY } = getClientCoords(e);
    const dx = clientX - dragState.value.startX;
    const dy = clientY - dragState.value.startY;
    const s = dragState.value.startBox;
    const maxW = displayWidth.value;
    const maxH = displayHeight.value;
    const mode = dragState.value.mode;

    if (mode === 'move') {
        const newX = Math.min(Math.max(0, s.x + dx), maxW - s.width);
        const newY = Math.min(Math.max(0, s.y + dy), maxH - s.height);
        box.value = { ...s, x: newX, y: newY };
        return;
    }

    let left = s.x;
    let top = s.y;
    let right = s.x + s.width;
    let bottom = s.y + s.height;

    if (mode.includes('w')) left = Math.min(Math.max(0, s.x + dx), right - MIN_SIZE);
    if (mode.includes('e')) right = Math.max(Math.min(maxW, s.x + s.width + dx), left + MIN_SIZE);
    if (mode.includes('n')) top = Math.min(Math.max(0, s.y + dy), bottom - MIN_SIZE);
    if (mode.includes('s')) bottom = Math.max(Math.min(maxH, s.y + s.height + dy), top + MIN_SIZE);

    let newW = right - left;
    let newH = bottom - top;

    // Enforce locked aspect ratio if specified
    if (props.aspectRatio) {
        if (mode === 'n' || mode === 's') {
            newW = newH * props.aspectRatio;
            if (left + newW > maxW) {
                newW = maxW - left;
                newH = newW / props.aspectRatio;
            }
        } else {
            newH = newW / props.aspectRatio;
            if (mode.includes('n')) {
                top = bottom - newH;
                if (top < 0) {
                    top = 0;
                    newH = bottom;
                    newW = newH * props.aspectRatio;
                    if (mode.includes('w')) left = right - newW;
                }
            } else if (top + newH > maxH) {
                newH = maxH - top;
                newW = newH * props.aspectRatio;
                if (mode.includes('w')) left = right - newW;
            }
        }
    }

    box.value = {
        x: Math.round(left),
        y: Math.round(top),
        width: Math.round(newW),
        height: Math.round(newH),
    };
};

const stopInteraction = () => {
    dragState.value.active = false;
    dragState.value.mode = null;
};

// Dimmed mask overlays outside the crop box
const maskStyles = computed(() => ({
    top: { top: 0, left: 0, width: '100%', height: `${box.value.y}px` },
    bottom: {
        top: `${box.value.y + box.value.height}px`,
        left: 0,
        width: '100%',
        height: `${Math.max(0, displayHeight.value - (box.value.y + box.value.height))}px`,
    },
    left: {
        top: `${box.value.y}px`,
        left: 0,
        width: `${box.value.x}px`,
        height: `${box.value.height}px`,
    },
    right: {
        top: `${box.value.y}px`,
        left: `${box.value.x + box.value.width}px`,
        width: `${Math.max(0, displayWidth.value - (box.value.x + box.value.width))}px`,
        height: `${box.value.height}px`,
    },
}));

const applyCrop = () => {
    if (!imageSrc.value || !displayWidth.value) return;

    const img = new Image();
    img.onload = () => {
        const scaleX = naturalWidth.value / displayWidth.value;
        const scaleY = naturalHeight.value / displayHeight.value;

        const sx = Math.round(box.value.x * scaleX);
        const sy = Math.round(box.value.y * scaleY);
        const sWidth = Math.round(box.value.width * scaleX);
        const sHeight = Math.round(box.value.height * scaleY);

        // Optimize output resolution while preserving cropped proportions
        const targetW = Math.min(sWidth, props.outputMaxWidth);
        const targetH = Math.round((targetW / sWidth) * sHeight);

        const canvas = document.createElement('canvas');
        canvas.width = targetW;
        canvas.height = targetH;

        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(img, sx, sy, sWidth, sHeight, 0, 0, targetW, targetH);

        canvas.toBlob(
            (blob) => {
                if (!blob) return;
                const baseName = (props.imageFile?.name || 'cropped-photo').replace(/\.[^/.]+$/, '');
                const croppedFile = new File([blob], `${baseName}.webp`, {
                    type: 'image/webp',
                    lastModified: Date.now(),
                });
                emit('cropped', croppedFile);
            },
            'image/webp',
            0.88
        );
    };
    img.src = imageSrc.value;
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="lg">
        <div
            class="p-5 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 select-none"
            @mousemove="onMove"
            @mouseup="stopInteraction"
            @mouseleave="stopInteraction"
            @touchmove.prevent="onMove"
            @touchend="stopInteraction"
        >
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-tight text-slate-900 dark:text-white">
                        {{ title }}
                    </h3>
                    <p class="text-[10px] font-bold text-slate-400 mt-0.5">
                        Drag the box to move or pull the corners/edges to resize
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="initDefaultBox"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                        title="Reset crop box"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="p-1.5 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Full Image + Draggable/Resizable Crop Box -->
            <div class="flex justify-center items-center bg-slate-950 rounded-lg p-4 overflow-hidden">
                <div
                    v-if="imageSrc"
                    class="relative overflow-hidden"
                    :style="{ width: `${displayWidth}px`, height: `${displayHeight}px` }"
                >
                    <img
                        :src="imageSrc"
                        draggable="false"
                        class="block w-full h-full pointer-events-none"
                    />

                    <!-- Darkened Area Outside Crop Box -->
                    <div class="absolute bg-black/60 pointer-events-none" :style="maskStyles.top"></div>
                    <div class="absolute bg-black/60 pointer-events-none" :style="maskStyles.bottom"></div>
                    <div class="absolute bg-black/60 pointer-events-none" :style="maskStyles.left"></div>
                    <div class="absolute bg-black/60 pointer-events-none" :style="maskStyles.right"></div>

                    <!-- Crop Selection Box -->
                    <div
                        class="absolute border-2 border-white cursor-move shadow-sm"
                        :style="{
                            left: `${box.x}px`,
                            top: `${box.y}px`,
                            width: `${box.width}px`,
                            height: `${box.height}px`,
                        }"
                        @mousedown="startInteraction('move', $event)"
                        @touchstart="startInteraction('move', $event)"
                    >
                        <!-- Rule of Thirds Grid -->
                        <div class="w-full h-full grid grid-cols-3 grid-rows-3 pointer-events-none">
                            <div class="border-r border-b border-white/30"></div>
                            <div class="border-r border-b border-white/30"></div>
                            <div class="border-b border-white/30"></div>
                            <div class="border-r border-b border-white/30"></div>
                            <div class="border-r border-b border-white/30"></div>
                            <div class="border-b border-white/30"></div>
                            <div class="border-r border-white/30"></div>
                            <div class="border-r border-white/30"></div>
                            <div></div>
                        </div>

                        <!-- 4 Corner Handles -->
                        <span
                            class="absolute -top-1.5 -left-1.5 w-3.5 h-3.5 bg-white border border-blue-600 rounded-xs cursor-nwse-resize"
                            @mousedown="startInteraction('nw', $event)"
                            @touchstart="startInteraction('nw', $event)"
                        ></span>
                        <span
                            class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 bg-white border border-blue-600 rounded-xs cursor-nesw-resize"
                            @mousedown="startInteraction('ne', $event)"
                            @touchstart="startInteraction('ne', $event)"
                        ></span>
                        <span
                            class="absolute -bottom-1.5 -left-1.5 w-3.5 h-3.5 bg-white border border-blue-600 rounded-xs cursor-nesw-resize"
                            @mousedown="startInteraction('sw', $event)"
                            @touchstart="startInteraction('sw', $event)"
                        ></span>
                        <span
                            class="absolute -bottom-1.5 -right-1.5 w-3.5 h-3.5 bg-white border border-blue-600 rounded-xs cursor-nwse-resize"
                            @mousedown="startInteraction('se', $event)"
                            @touchstart="startInteraction('se', $event)"
                        ></span>

                        <!-- 4 Edge Handles -->
                        <span
                            class="absolute -top-1 left-1/2 -translate-x-1/2 w-6 h-2 bg-white border border-blue-600 rounded-xs cursor-ns-resize"
                            @mousedown="startInteraction('n', $event)"
                            @touchstart="startInteraction('n', $event)"
                        ></span>
                        <span
                            class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-2 bg-white border border-blue-600 rounded-xs cursor-ns-resize"
                            @mousedown="startInteraction('s', $event)"
                            @touchstart="startInteraction('s', $event)"
                        ></span>
                        <span
                            class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-6 bg-white border border-blue-600 rounded-xs cursor-ew-resize"
                            @mousedown="startInteraction('w', $event)"
                            @touchstart="startInteraction('w', $event)"
                        ></span>
                        <span
                            class="absolute top-1/2 -right-1 -translate-y-1/2 w-2 h-6 bg-white border border-blue-600 rounded-xs cursor-ew-resize"
                            @mousedown="startInteraction('e', $event)"
                            @touchstart="startInteraction('e', $event)"
                        ></span>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-2 mt-5 pt-3 border-t border-slate-100 dark:border-slate-700">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-3.5 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white transition"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="applyCrop"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white shadow-sm transition"
                >
                    <Check class="w-3.5 h-3.5" />
                    Crop &amp; Use Photo
                </button>
            </div>
        </div>
    </Modal>
</template>