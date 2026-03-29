<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Calendar } from 'v-calendar';
import 'v-calendar/dist/style.css';
import Modal from '@/Components/Modal.vue'; // <-- Import the Modal component

const props = defineProps({
    attributes: Array
});

const page = usePage();
const viewMode = ref('monthly'); 

// --- MODAL LOGIC ---
const isModalOpen = ref(false);
const selectedDay = ref(null);

const openDayModal = (day, attributes) => {
    // Only open the modal if there are actually events on that day
    if (attributes.length > 0) {
        selectedDay.value = { day, attributes };
        isModalOpen.value = true;
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => selectedDay.value = null, 300); // Clear data after animation finishes
};

// Generate correct link based on user role
const getEventLink = (attr) => {
    if (page.props.auth.user.role === 'teacher') {
        return route('teacher.assignments.show', attr.customData.id);
    }
    return route('student.courses.show', attr.customData.course_id); 
};
</script>

<template>
    <Head title="Academic Calendar" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto px-2 sm:px-4">
            
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Calendar</h1>
                    <p class="text-slate-500 text-[9px] uppercase font-bold tracking-widest">Deadlines</p>
                </div>
                
                <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button 
                        @click="viewMode = 'monthly'" 
                        :class="{'bg-white dark:bg-slate-600 shadow-sm text-slate-900 dark:text-white': viewMode === 'monthly', 'text-slate-500': viewMode !== 'monthly'}" 
                        class="px-3 py-1 text-[10px] font-bold rounded uppercase tracking-wider transition-all"
                    >
                        Month
                    </button>
                    <button 
                        @click="viewMode = 'weekly'" 
                        :class="{'bg-white dark:bg-slate-600 shadow-sm text-slate-900 dark:text-white': viewMode === 'weekly', 'text-slate-500': viewMode !== 'weekly'}" 
                        class="px-3 py-1 text-[10px] font-bold rounded uppercase tracking-wider transition-all"
                    >
                        Week
                    </button>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <Calendar 
                    is-expanded 
                    borderless
                    :view="viewMode" 
                    title-position="left"
                    :attributes="attributes"
                    class="ultra-compact-calendar"
                    :is-dark="$page.props.auth.user.theme === 'dark'" 
                >
                    <template #day-content="{ day, attributes }">
                        <div 
                            @click="openDayModal(day, attributes)"
                            class="flex flex-col h-full z-10 overflow-hidden p-0.5 border-t border-slate-100 dark:border-slate-800/60 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/30 cursor-pointer group"
                        >
                            <span class="text-[9px] font-bold text-slate-400 mb-0.5 ml-0.5" :class="{'text-blue-500 dark:text-blue-400': attributes.length > 0}">
                                {{ day.day }}
                            </span>
                            
                            <div class="flex-grow overflow-hidden space-y-0.5">
                                <Link
                                    v-for="attr in attributes.slice(0, 3)"
                                    :key="attr.key"
                                    :href="getEventLink(attr)"
                                    @click.stop
                                    class="block px-1.5 py-0.5 rounded text-[8px] font-bold text-white truncate shadow-sm hover:opacity-80 transition-opacity"
                                    :class="attr.customData.class"
                                    :title="attr.customData.title"
                                >
                                    {{ attr.customData.title }}
                                </Link>

                                <div v-if="attributes.length > 3" class="text-[8px] font-bold text-slate-500 dark:text-slate-400 text-center mt-1 group-hover:text-blue-600 transition-colors">
                                    +{{ attributes.length - 3 }} more
                                </div>
                            </div>
                        </div>
                    </template>
                </Calendar>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">
                    Activities for {{ selectedDay?.day?.ariaLabel }}
                </h2>
                
                <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <Link
                        v-for="attr in selectedDay?.attributes"
                        :key="attr.key"
                        :href="getEventLink(attr)"
                        @click="closeModal"
                        class="block p-3 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full shrink-0" :class="attr.customData.class"></div>
                            <div class="overflow-hidden">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ attr.customData.title }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5 uppercase tracking-wider">
                                    {{ attr.customData.type }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="closeModal" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-md text-sm font-bold hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
/* --- ULTRA COMPACT CSS --- */
.ultra-compact-calendar.vc-container {
    --vc-bg: transparent;
    --vc-border: transparent;
    width: 100%;
}
.ultra-compact-calendar .vc-pane-container {
    width: 100%;
}
.ultra-compact-calendar .vc-weeks {
    display: grid;
    grid-template-rows: repeat(auto-fit, minmax(45px, 1fr)); 
}
@media (min-width: 640px) {
    .ultra-compact-calendar .vc-weeks {
        grid-template-rows: repeat(auto-fit, minmax(70px, 1fr)); 
    }
}
.ultra-compact-calendar .vc-header {
    padding: 6px 10px;
}
.ultra-compact-calendar .vc-title {
    font-size: 0.8rem;
    font-weight: 700;
}
/* Scrollbar styling for the modal */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>