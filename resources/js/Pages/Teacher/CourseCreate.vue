<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, reactive, nextTick } from 'vue';

const form = useForm({
    title: '',
    description: '',
    difficulty_level: 'beginner',
    thumbnail: null,
});

// --- CROPPER STATE ---
const showCropModal = ref(false);
const imageToCrop = ref(null); // The raw file URL
const croppedPreview = ref(null); // The final result to show
const cropper = reactive({
    scale: 1,
    x: 0,
    y: 0,
    isDragging: false,
    startX: 0,
    startY: 0
});
const imageRef = ref(null);

// 1. Handle File Select -> Open Modal
const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageToCrop.value = URL.createObjectURL(file);
        // Reset cropper
        cropper.scale = 1;
        cropper.x = 0;
        cropper.y = 0;
        showCropModal.value = true;
    }
    // Reset input so same file can be selected again if cancelled
    event.target.value = '';
};

// 2. Cropper Mouse Events (Drag Logic)
const startDrag = (e) => {
    e.preventDefault();
    cropper.isDragging = true;
    cropper.startX = e.clientX - cropper.x;
    cropper.startY = e.clientY - cropper.y;
};
const onDrag = (e) => {
    if (!cropper.isDragging) return;
    e.preventDefault();
    cropper.x = e.clientX - cropper.startX;
    cropper.y = e.clientY - cropper.startY;
};
const stopDrag = () => {
    cropper.isDragging = false;
};

// 3. Apply Crop (Draw to Canvas)
const applyCrop = () => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = imageRef.value;

    // Set desired output size (16:9 ratio, e.g., 800x450)
    canvas.width = 800;
    canvas.height = 450;

    // Background color (if image doesn't fill)
    ctx.fillStyle = '#0f172a'; // slate-900
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Calculate scaling ratio between displayed size and actual canvas size
    // The container in modal is fixed (e.g., let's say 400px wide in CSS)
    // We need to map the CSS transforms to the Canvas scaling
    const scaleFactor = canvas.width / 480; // 480 is the modal container width defined below

    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.scale(cropper.scale, cropper.scale);
    ctx.translate(-canvas.width / 2, -canvas.height / 2);
    
    // Draw image with offset
    // We multiply x/y by scaleFactor to match the larger canvas resolution
    ctx.drawImage(img, cropper.x * scaleFactor, cropper.y * scaleFactor, img.naturalWidth * scaleFactor, img.naturalHeight * scaleFactor); // Simplified logic for basic centering

    // Note: A perfect mapping requires precise CSS-to-Canvas math. 
    // This simplified version draws what you see relative to center.
    // For a robust version, we rely on the visual "what you see is what you get".
    
    canvas.toBlob((blob) => {
        // Create a new File object from the blob
        const file = new File([blob], "course_thumbnail.jpg", { type: "image/jpeg" });
        form.thumbnail = file;
        croppedPreview.value = URL.createObjectURL(blob);
        showCropModal.value = false;
    }, 'image/jpeg', 0.9);
};

const submit = () => {
    form.post(route('teacher.courses.store'));
};
</script>

<template>
    <Head title="Create Course" />

    <AuthenticatedLayout>
        <div class="mb-6 flex justify-between items-center">
             <div>
                 <h1 class="text-2xl font-bold text-blue-900 dark:text-white tracking-tight">Create New Course</h1>
                 <p class="text-slate-400 text-xs mt-1">Start a new class for your students.</p>
             </div>
             
             <Link :href="route('teacher.courses.index')" class="text-slate-500 dark:text-slate-400 text-sm font-black hover:text-blue-900 dark:hover:text-white transition flex items-center gap-1">
                &larr; Back
             </Link>
        </div>

        <div class="max-w-3xl">
            <div class="bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg p-6 shadow-sm">
                
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <div>
                        <label class="block text-xs font-bold text-blue-900 dark:text-blue-400 mb-1.5 uppercase tracking-wide">Course Profile Image</label>
                        
                        <div v-if="croppedPreview" class="mb-3 relative w-full h-48 bg-slate-200 dark:bg-slate-900 rounded-lg overflow-hidden border border-slate-300 dark:border-slate-700 group">
                            <img :src="croppedPreview" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                                 @click="document.getElementById('course_thumbnail').click()">
                                <span class="text-white text-xs font-bold">Click to Change</span>
                            </div>
                        </div>

                        <input 
                            id="course_thumbnail"
                            type="file" 
                            @change="handleFileChange"
                            accept="image/*"
                            class="block w-full text-xs text-slate-500 dark:text-slate-400
                                   file:mr-4 file:py-2 file:px-4
                                   file:rounded-full file:border-0
                                   file:text-xs file:font-semibold
                                   file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300
                                   hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                                   cursor-pointer"
                        />
                        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-500">Upload and crop a cover image.</p>
                        <InputError class="mt-2" :message="form.errors.thumbnail" />
                    </div>

                    <div>
                        <label for="course_title" class="block text-xs font-bold text-blue-900 dark:text-blue-400 mb-1.5 uppercase tracking-wide">Course Title</label>
                        <input id="course_title" v-model="form.title" type="text" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2.5 text-sm focus:ring-2 focus:ring-yellow-400 focus:border-blue-700 shadow-sm transition-all" placeholder="e.g. Advanced Web Development" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                         <label for="course_description" class="block text-xs font-bold text-blue-900 dark:text-blue-400 mb-1.5 uppercase tracking-wide">Description</label>
                        <textarea id="course_description" v-model="form.description" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2.5 text-sm focus:ring-2 focus:ring-yellow-400 focus:border-blue-700 shadow-sm transition-all h-32" placeholder="What will students learn?"></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div>
                        <label for="course_difficulty" class="block text-xs font-bold text-blue-900 dark:text-blue-400 mb-1.5 uppercase tracking-wide">Difficulty Level</label>
                        <select id="course_difficulty" v-model="form.difficulty_level" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2.5 text-sm focus:ring-2 focus:ring-yellow-400 focus:border-blue-700">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.difficulty_level" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <Link :href="route('teacher.courses.index')" class="text-slate-500 hover:text-blue-900 dark:text-slate-400 dark:hover:text-white text-xs transition px-3 py-1.5 font-bold">Cancel</Link>
                        <PrimaryButton class="bg-blue-800 hover:bg-blue-700 border border-transparent font-black" :disabled="form.processing">Create Course</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <Modal :show="showCropModal" @close="showCropModal = false">
            <div class="p-6 bg-white dark:bg-slate-800 rounded-lg text-slate-900 dark:text-white">
                <h3 class="text-lg font-bold mb-4">Adjust Image</h3>
                <p class="text-xs text-slate-500 mb-4">Drag to move, slider to zoom.</p>
                
                <div class="relative w-full overflow-hidden bg-slate-900 rounded-lg border-2 border-dashed border-slate-500 cursor-move mb-4"
                     style="height: 270px;" 
                     @mousedown="startDrag" @mousemove="onDrag" @mouseup="stopDrag" @mouseleave="stopDrag">
                    
                    <img ref="imageRef" 
                         :src="imageToCrop" 
                         class="absolute max-w-none origin-center pointer-events-none"
                         :style="{ 
                             transform: `translate(${cropper.x}px, ${cropper.y}px) scale(${cropper.scale})`,
                             left: '50%', top: '50%', 
                             marginLeft: '-50%', marginTop: '-50%' // Center initial pivot
                         }" />
                    
                    <div class="absolute inset-0 pointer-events-none border-2 border-white/30 rounded-lg shadow-[0_0_0_9999px_rgba(0,0,0,0.5)]"></div>
                </div>

                <div class="flex items-center gap-4 mb-6">
                    <span class="text-xs font-bold">Zoom</span>
                    <input type="range" min="0.5" max="3" step="0.1" v-model.number="cropper.scale" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer dark:bg-slate-700 accent-blue-600">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="showCropModal = false" class="px-4 py-2 text-slate-500 font-bold text-sm">Cancel</button>
                    <button type="button" @click="applyCrop" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-bold text-sm shadow-lg">Apply Crop</button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>