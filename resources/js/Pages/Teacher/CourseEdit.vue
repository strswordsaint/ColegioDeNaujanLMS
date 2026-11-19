<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ImageCropperModal from '@/Components/ImageCropperModal.vue';
import { Head, useForm, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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

// --- STANDARD BOX CROPPER STATE ---
const showCropModal = ref(false);
const selectedFileToCrop = ref(null);
const newImagePreview = ref(null);

const currentDisplayImage = computed(() => {
    if (newImagePreview.value) return newImagePreview.value;
    if (props.course.thumbnail && !imageError.value) {
        const cleanPath = props.course.thumbnail.replace(/^\/storage\//, '');
        return `${usePage().props.env.AWS_URL}/${cleanPath}`;
    }
    return null;
});

const handleFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        selectedFileToCrop.value = file;
        showCropModal.value = true;
    }
    event.target.value = '';
};

// Re-crop either a newly picked file or the existing S3 thumbnail
const editExistingImage = async () => {
    if (form.thumbnail instanceof File) {
        selectedFileToCrop.value = form.thumbnail;
        showCropModal.value = true;
        return;
    }

    if (currentDisplayImage.value) {
        try {
            const response = await fetch(currentDisplayImage.value);
            const blob = await response.blob();
            selectedFileToCrop.value = new File([blob], 'course_thumbnail.jpg', { type: blob.type || 'image/jpeg' });
            showCropModal.value = true;
        } catch (err) {
            alert('Please click "Upload New" to select and crop a new image.');
        }
    }
};

const handleCroppedFile = (croppedFile) => {
    form.thumbnail = croppedFile;
    if (newImagePreview.value) {
        URL.revokeObjectURL(newImagePreview.value);
    }
    newImagePreview.value = URL.createObjectURL(croppedFile);
    showCropModal.value = false;
    selectedFileToCrop.value = null;
};

const closeCropModal = () => {
    showCropModal.value = false;
    selectedFileToCrop.value = null;
};

const submit = () => {
    form.post(route('teacher.courses.update', props.course.id), {
        forceFormData: true,
    });
};
const deleteCourse = () => {
    if (confirm('Are you really sure?')) router.delete(route('teacher.courses.destroy', props.course.id));
};
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
                            <img v-if="currentDisplayImage" :src="currentDisplayImage" @error="imageError = true" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-[10px] font-bold uppercase">No Image</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Title</label>
                            <input type="text" v-model="form.title" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2 text-xs focus:ring-blue-500 focus:border-blue-500" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Year Level</label>
                            <select v-model="form.difficulty_level" class="w-full bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded p-2 text-xs focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                                <option value="beginner">1st Year</option>
                                <option value="intermediate">2nd Year</option>
                                <option value="advanced">3rd Year</option>
                                <option value="final">4th Year</option>
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

        <ImageCropperModal
            :show="showCropModal"
            :image-file="selectedFileToCrop"
            :aspect-ratio="16 / 9"
            :output-max-width="800"
            title="Crop Thumbnail"
            @close="closeCropModal"
            @cropped="handleCroppedFile"
        />
    </AuthenticatedLayout>
</template>