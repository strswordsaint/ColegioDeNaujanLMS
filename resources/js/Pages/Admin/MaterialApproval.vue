<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ 
    materials: Array,
    requireApproval: Boolean 
});

const activeTab = ref('pending');
const selectedIds = ref([]);
const layoutRef = ref(null);

// --- COURSE DROPDOWN FILTER LOGIC ---
const selectedCourseFilter = ref('all');

const availableCourses = computed(() => {
    const courses = {};
    props.materials.forEach(m => {
        if (m.course) courses[m.course.id] = m.course.title;
    });
    return Object.entries(courses).map(([id, title]) => ({ id: Number(id), title }));
});

// --- EDIT MODAL LOGIC ---
const showEditModal = ref(false);
const lessonToEdit = ref(null);

const editForm = useForm({
    title: '',
    available_from: '',
    available_until: ''
});

// Formats DB datetime to HTML datetime-local input
const formatForInput = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    return new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
};

const openEditModal = (lesson) => {
    lessonToEdit.value = lesson;
    editForm.title = lesson.title;
    editForm.available_from = formatForInput(lesson.available_from);
    editForm.available_until = formatForInput(lesson.available_until);
    editForm.clearErrors();
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.patch(route('admin.lessons.update', lessonToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            lessonToEdit.value = null;
        }
    });
};

// --- REJECTION MODAL LOGIC ---
const selectedLesson = ref(null);
const rejectionForm = useForm({ reason: '' });

const openRejectModal = (lesson) => {
    selectedLesson.value = lesson;
    rejectionForm.reason = '';
};

const submitRejection = () => {
    rejectionForm.patch(route('admin.lessons.reject', selectedLesson.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedLesson.value = null;
            rejectionForm.reset();
        },
    });
};

const toggleSelection = (id) => {
    if (selectedIds.value.includes(id)) selectedIds.value = selectedIds.value.filter(i => i !== id);
    else selectedIds.value.push(id);
};

const toggleAll = () => {
    if (selectedIds.value.length === pendingMaterials.value.length) selectedIds.value = [];
    else selectedIds.value = pendingMaterials.value.map(m => m.id);
};

// --- FILTER LOGIC ---
const pendingMaterials = computed(() => props.materials.filter(m => m.approval_status === 'pending'));
const rejectedMaterials = computed(() => props.materials.filter(m => m.approval_status === 'rejected'));
const approvedMaterials = computed(() => {
    const now = new Date();
    return props.materials.filter(m => m.approval_status === 'approved' && (!m.available_until || new Date(m.available_until) > now));
});
const archivedMaterials = computed(() => {
    const now = new Date();
    return props.materials.filter(m => m.approval_status === 'approved' && m.available_until && new Date(m.available_until) <= now);
});

// Applies Tab and Course Dropdown Filter
const displayedMaterials = computed(() => {
    let filtered = activeTab.value === 'pending' ? pendingMaterials.value :
                   activeTab.value === 'approved' ? approvedMaterials.value :
                   activeTab.value === 'rejected' ? rejectedMaterials.value :
                   archivedMaterials.value;

    if (selectedCourseFilter.value !== 'all') {
        filtered = filtered.filter(m => m.course_id === selectedCourseFilter.value);
    }
    return filtered;
});

// --- GROUP BY COURSE LOGIC ---
const groupedMaterials = computed(() => {
    const groups = {};
    displayedMaterials.value.forEach(material => {
        const courseName = material.course?.title || 'Unassigned / Deleted Course';
        if (!groups[courseName]) {
            groups[courseName] = { teacher: material.course?.teacher?.name, items: [] };
        }
        groups[courseName].items.push(material);
    });
    return groups;
});

watch(activeTab, () => { selectedIds.value = []; });

// --- ACTIONS ---
const bulkApprove = () => {
    if (!selectedIds.value.length) return;
    router.patch(route('admin.lessons.bulk-approve'), { lesson_ids: selectedIds.value }, { preserveScroll: true, onSuccess: () => { selectedIds.value = []; }});
};
const approveMaterial = (id) => router.patch(route('admin.lessons.approve', id), {}, { preserveScroll: true });
const archiveMaterial = (id) => { if(confirm('Force this material into the archive?')) router.patch(route('admin.lessons.archive', id), {}, { preserveScroll: true }); };
const unarchiveMaterial = (id) => router.patch(route('admin.lessons.unarchive', id), {}, { preserveScroll: true });
const deleteMaterial = (id) => { if(confirm('Permanently delete this material?')) router.delete(route('teacher.lessons.destroy', id), { preserveScroll: true }); };
</script>

<template>
    <Head title="Material Approval" />

    <AuthenticatedLayout ref="layoutRef">
        <div class="mb-4 flex flex-col md:flex-row md:items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Material Approval</h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Manage Course Uploads</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm flex items-center gap-2">
                    <div class="flex flex-col text-right">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-300 leading-none">Require Approval</span>
                        <span class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">{{ requireApproval ? 'Active' : 'Auto-Approve' }}</span>
                    </div>
                    <button @click="layoutRef.confirmToggle()" :class="requireApproval ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none shadow-inner">
                        <span :class="requireApproval ? 'translate-x-4' : 'translate-x-0'" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                    <button v-if="activeTab === 'pending' && selectedIds.length > 0" @click="bulkApprove" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm transition-all flex items-center gap-1 h-full">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Approve ({{ selectedIds.length }})
                    </button>
                </transition>
            </div>
        </div>

        <div class="max-w-6xl space-y-4">
            
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg gap-1 overflow-x-auto shadow-sm border border-slate-200 dark:border-slate-700 flex-1 scrollbar-hide">
                    <button @click="activeTab = 'pending'" class="shrink-0 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-md transition-all flex items-center justify-center gap-1" :class="activeTab === 'pending' ? 'bg-white dark:bg-slate-700 text-orange-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Pending <span v-if="pendingMaterials.length" class="bg-orange-100 text-orange-600 px-1 rounded text-[8px]">{{ pendingMaterials.length }}</span>
                    </button>
                    <button @click="activeTab = 'approved'" class="shrink-0 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-md transition-all" :class="activeTab === 'approved' ? 'bg-white dark:bg-slate-700 text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Approved
                    </button>
                    <button @click="activeTab = 'rejected'" class="shrink-0 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-md transition-all flex items-center justify-center gap-1" :class="activeTab === 'rejected' ? 'bg-white dark:bg-slate-700 text-red-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Rejected <span v-if="rejectedMaterials.length" class="bg-red-100 text-red-600 px-1 rounded text-[8px]">{{ rejectedMaterials.length }}</span>
                    </button>
                    <button @click="activeTab = 'archived'" class="shrink-0 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest rounded-md transition-all" :class="activeTab === 'archived' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                        Archived
                    </button>
                </div>

                <div class="shrink-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 shadow-sm flex items-center gap-1.5 min-w-[160px] w-full sm:w-auto">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    <select v-model="selectedCourseFilter" class="bg-transparent border-none text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 w-full focus:ring-0 cursor-pointer p-0 m-0">
                        <option value="all">All Courses</option>
                        <option v-for="c in availableCourses" :key="c.id" :value="c.id">{{ c.title }}</option>
                    </select>
                </div>
            </div>

            <div v-if="Object.keys(groupedMaterials).length > 0" class="space-y-4">
                <div v-for="(group, courseName) in groupedMaterials" :key="courseName" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-2.5 px-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <div class="w-6 h-6 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-black text-xs text-slate-800 dark:text-slate-100 uppercase tracking-tight truncate">{{ courseName }}</h3>
                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mt-0.5 truncate">Tchr: {{ group.teacher || 'Unknown' }}</p>
                            </div>
                        </div>
                        <span class="text-[9px] font-black bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 rounded text-slate-500 shadow-sm shrink-0">{{ group.items.length }} Items</span>
                    </div>

                    <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <div v-for="material in group.items" :key="material.id" class="p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 transition-colors" :class="selectedIds.includes(material.id) ? 'bg-emerald-50/30 dark:bg-emerald-900/10' : 'hover:bg-slate-50/50 dark:hover:bg-slate-800/50'">
                            <div class="flex items-center gap-2.5 min-w-0 w-full sm:w-auto cursor-pointer" @click="activeTab === 'pending' ? toggleSelection(material.id) : null">
                                <div v-if="activeTab === 'pending'" class="shrink-0">
                                    <input type="checkbox" :checked="selectedIds.includes(material.id)" @change.stop="toggleSelection(material.id)" class="w-3.5 h-3.5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                </div>
                                <div class="flex flex-col truncate">
                                    <h3 class="font-black text-xs text-slate-900 dark:text-white leading-tight truncate">{{ material.title }}</h3>
                                    <p class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">Up: {{ new Date(material.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>

                            <div v-if="requireApproval" class="flex flex-wrap items-center justify-end gap-1.5 w-full sm:w-auto shrink-0">
                                
                                <a :href="`/storage/${material.attachment_path}`" target="_blank" class="text-[9px] font-bold uppercase tracking-wide bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1.5 rounded flex items-center gap-1 hover:bg-slate-200 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><span class="hidden sm:inline">View</span>
                                </a>
                                <a :href="`/storage/${material.attachment_path}`" download class="text-[9px] font-bold uppercase tracking-wide bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1.5 rounded flex items-center gap-1 hover:bg-blue-100 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg><span class="hidden sm:inline">DL</span>
                                </a>
                                
                                <button @click="openEditModal(material)" class="text-[9px] font-bold uppercase tracking-wide bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1.5 rounded flex items-center gap-1 hover:bg-slate-200 transition">
                                    Edit
                                </button>

                                <button v-if="activeTab === 'pending' || activeTab === 'rejected'" @click="approveMaterial(material.id)" class="text-[9px] font-bold uppercase tracking-wide bg-emerald-600 text-white px-2 py-1.5 rounded hover:bg-emerald-500 shadow-sm transition">Approve</button>
                                <button v-if="activeTab === 'approved'" @click="openRejectModal(material)" class="text-[9px] font-bold uppercase tracking-wide bg-red-100 text-red-600 px-2 py-1.5 rounded hover:bg-red-200 shadow-sm transition">Reject</button>
                                <button v-if="activeTab === 'approved'" @click="archiveMaterial(material.id)" class="text-[9px] font-bold uppercase tracking-wide bg-orange-100 text-orange-600 px-2 py-1.5 rounded hover:bg-orange-200 shadow-sm transition">Archive</button>
                                <button v-if="activeTab === 'archived'" @click="unarchiveMaterial(material.id)" class="text-[9px] font-bold uppercase tracking-wide bg-blue-100 text-blue-600 px-2 py-1.5 rounded hover:bg-blue-200 shadow-sm transition">Unarchive</button>
                                
                                <button @click="deleteMaterial(material.id)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            <div v-else class="text-[9px] font-black text-slate-400 uppercase italic">Bypass Active</div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 bg-slate-50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">No materials found.</p>
            </div>
        </div>

        <Modal :show="showEditModal" @close="showEditModal = false" maxWidth="sm">
            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">Edit Details</h3>
                <form @submit.prevent="submitEdit" class="space-y-3">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Material Title *</label>
                        <input v-model="editForm.title" type="text" class="w-full flex h-8 rounded-md border border-slate-200 bg-transparent px-2 py-1 text-xs shadow-sm focus-visible:ring-1 focus-visible:ring-slate-400 dark:border-slate-800" required>
                        <InputError class="mt-1 text-[9px]" :message="editForm.errors.title" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Show From *</label>
                            <input v-model="editForm.available_from" type="datetime-local" class="w-full flex h-8 rounded-md border border-slate-200 bg-transparent px-2 py-1 text-[10px] font-medium shadow-sm dark:border-slate-800 text-slate-700 dark:text-slate-300" required>
                            <InputError class="mt-1 text-[9px]" :message="editForm.errors.available_from" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Archive On *</label>
                            <input v-model="editForm.available_until" type="datetime-local" class="w-full flex h-8 rounded-md border border-slate-200 bg-transparent px-2 py-1 text-[10px] font-medium shadow-sm dark:border-slate-800 text-slate-700 dark:text-slate-300" required>
                            <InputError class="mt-1 text-[9px]" :message="editForm.errors.available_until" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showEditModal = false" class="text-[9px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 uppercase tracking-widest">Cancel</button>
                        <button class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded-md text-[9px] font-black uppercase tracking-widest shadow-sm transition-colors" :disabled="editForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="!!selectedLesson" @close="selectedLesson = null" maxWidth="sm">
            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h2 class="text-sm font-black uppercase tracking-tight text-red-600 mb-2">Reject Material</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400">Provide feedback for "{{ selectedLesson?.title }}".</p>
                <textarea v-model="rejectionForm.reason" rows="3" class="mt-3 w-full rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 text-xs shadow-sm" placeholder="Explain what needs to be fixed..."></textarea>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="selectedLesson = null" class="text-[9px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 uppercase tracking-widest">Cancel</button>
                    <button @click="submitRejection" :disabled="rejectionForm.processing" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded-md text-[9px] font-black uppercase tracking-widest shadow-sm transition-colors">Send Feedback</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Hides scrollbar for the horizontal tab container but allows scrolling */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>