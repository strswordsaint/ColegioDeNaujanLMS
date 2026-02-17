<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    course: Object,
    backUrl: String 
});

const form = useForm({
    _method: 'PATCH',
    title: props.course.title,
    description: props.course.description,
    difficulty_level: props.course.difficulty_level,
    thumbnail: null,
});

const imageError = ref(false);

// --- CROPPER STATE ---
const showCropModal = ref(false);
const imageToCrop = ref(null);
const newImagePreview = ref(null);
const cropper = reactive({ scale: 1, x: 0, y: 0, isDragging: false, startX: 0, startY: 0 });
const imageRef = ref(null);

// Helper to determine what image is currently being shown
const currentDisplayImage = computed(() => {
    if (newImagePreview.value) return newImagePreview.value;
    if (props.course.thumbnail && !imageError.value) return props.course.thumbnail;
    return null;
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        imageToCrop.value = URL.createObjectURL(file);
        resetCropper();
        showCropModal.value = true;
    }
    event.target.value = '';
};

// NEW: Edit the image that is currently visible
const editExistingImage = () => {
    if (currentDisplayImage.value) {
        imageToCrop.value = currentDisplayImage.value;
        resetCropper();
        showCropModal.value = true;
    }
};

const resetCropper = () => {
    cropper.scale = 1; cropper.x = 0; cropper.y = 0;
};

// Drag Logic
const startDrag = (e) => { e.preventDefault(); cropper.isDragging = true; cropper.startX = e.clientX - cropper.x; cropper.startY = e.clientY - cropper.y; };
const onDrag = (e) => { if (!cropper.isDragging) return; e.preventDefault(); cropper.x = e.clientX - cropper.startX; cropper.y = e.clientY - cropper.startY; };
const stopDrag = () => { cropper.isDragging = false; };

const applyCrop = () => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const img = imageRef.value;
    canvas.width = 800; canvas.height = 450;
    ctx.fillStyle = '#0f172a'; ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    const scaleFactor = canvas.width / 480; 
    ctx.translate(canvas.width/2, canvas.height/2);
    ctx.scale(cropper.scale, cropper.scale);
    ctx.translate(-canvas.width/2, -canvas.height/2);
    ctx.drawImage(img, cropper.x * scaleFactor, cropper.y * scaleFactor, img.naturalWidth * scaleFactor, img.naturalHeight * scaleFactor);

    canvas.toBlob((blob) => {
        // We create a new file from the blob so it can be uploaded
        const file = new File([blob], "course_thumbnail.jpg", { type: "image/jpeg" });
        form.thumbnail = file;
        newImagePreview.value = URL.createObjectURL(blob);
        showCropModal.value = false;
    }, 'image/jpeg', 0.9);
};

const submit = () => { form.post(route('teacher.courses.update', props.course.id)); };
const deleteCourse = () => { if (confirm('Are you really sure?')) router.delete(route('teacher.courses.destroy', props.course.id)); };
</script>

<template>
    <Head title="Course Settings" />

    <AuthenticatedLayout>
        
        <div class="max-w-lg mx-auto space-y-4">
            
            <div class="flex justify-between items-center pt-2">
                 <div>
                     <h1 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Course Settings</h1>
                     <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-0.5 font-medium">Update details for {{ course.title }}.</p>
                 </div>
                 
                 <Link :href="backUrl" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 bg-slate-100 hover:bg-slate-200 dark:text-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-md transition flex items-center gap-1 shadow-sm">
                     Back
                 </Link>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
                <form @submit.prevent="submit" class="space-y-4">
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-2 rounded border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Thumbnail</span>
                            
                            <div class="flex gap-2">
                                <button v-if="currentDisplayImage" type="button" @click="editExistingImage" class="text-[10px] font-bold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 px-2 py-0.5 rounded shadow-sm transition">
                                    Adjust Crop
                                </button>

                                <label class="cursor-pointer text-[10px] font-bold text-blue-600 hover:underline bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded border border-blue-100 dark:border-blue-900 transition">
                                    Upload New
                                    <input type="file" @change="handleFileChange" accept="image/*" class="hidden" />
                                </label>
                            </div>
                        </div>

                        <div class="relative w-full h-32 overflow-hidden rounded group bg-slate-200 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                            <img v-if="currentDisplayImage" :src="currentDisplayImage" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-[10px] font-bold uppercase">No Image</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Title</label>
                            <input type="text" v-model="form.title" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2 text-xs focus:ring-blue-500 focus:border-blue-500" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Difficulty</label>
                            <select v-model="form.difficulty_level" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2 text-xs focus:ring-blue-500 focus:border-blue-500">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div>
                         <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Description</label>
                        <textarea v-model="form.description" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2 text-xs h-24 resize-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end pt-2">
                        <PrimaryButton :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-xs px-4 py-2 border-transparent">Save Changes</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/30 rounded-lg p-4 shadow-sm flex items-center justify-between">
                <div class="text-[10px] text-red-600 dark:text-red-400 font-bold">Caution: This action cannot be undone.</div>
                <DangerButton @click="deleteCourse" class="text-[10px] px-3 py-1.5 h-auto">Delete Course</DangerButton>
            </div>
        </div>

        <Modal :show="showCropModal" @close="showCropModal = false">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-3">Adjust Thumbnail</h3>
                <div class="relative w-full overflow-hidden bg-slate-100 dark:bg-slate-900 rounded border border-slate-300 dark:border-slate-700 cursor-move mb-3" style="height: 200px;" @mousedown="startDrag" @mousemove="onDrag" @mouseup="stopDrag" @mouseleave="stopDrag">
                    <img ref="imageRef" :src="imageToCrop" class="absolute max-w-none origin-center pointer-events-none" :style="{ transform: `translate(${cropper.x}px, ${cropper.y}px) scale(${cropper.scale})`, left: '50%', top: '50%', marginLeft: '-50%', marginTop: '-50%' }" crossorigin="anonymous" />
                </div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-[10px] font-bold text-slate-500 uppercase">Zoom</span>
                    <input type="range" min="0.5" max="3" step="0.1" v-model.number="cropper.scale" class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-blue-600">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCropModal = false" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded text-xs font-bold transition shadow-sm border border-slate-200 dark:border-slate-600">Cancel</button>
                    <button type="button" @click="applyCrop" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-bold shadow-sm transition border border-transparent">Apply Crop</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>