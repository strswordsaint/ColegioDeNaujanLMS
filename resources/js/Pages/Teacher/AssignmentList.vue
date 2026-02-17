<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    courses: Array
});

const selectedCourseId = ref(props.courses.length > 0 ? props.courses[0].id : null);
const activeTab = ref('upcoming');
const imageErrors = ref({});

const selectCourse = (id) => { selectedCourseId.value = id; };
const handleImageError = (id) => { imageErrors.value[id] = true; };
const selectedCourse = computed(() => props.courses.find(c => c.id === selectedCourseId.value));

const countUpcoming = (assignments) => {
    if (!assignments) return 0;
    const now = new Date();
    return assignments.filter(a => {
        const dueDate = a.due_date ? new Date(a.due_date) : null;
        return !dueDate || dueDate >= now;
    }).length;
};

const filteredAssignments = computed(() => {
    if (!selectedCourse.value) return [];
    const now = new Date();
    return selectedCourse.value.assignments.filter(assignment => {
        const dueDate = assignment.due_date ? new Date(assignment.due_date) : null;
        return activeTab.value === 'upcoming' ? (!dueDate || dueDate >= now) : (dueDate && dueDate < now);
    });
});
</script>

<template>
    <Head title="Assignments" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-100px)] flex flex-col">
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-blue-900 tracking-tight">All Assignments</h1>
                <p class="text-slate-500 dark:text-slate-400 text-xs mt-1 font-medium">Manage tasks across all your classes.</p>
            </div>

            <div class="flex-1 flex gap-6 overflow-hidden">
                
                <aside class="w-72 bg-white dark:bg-slate-800 rounded-xl flex flex-col overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700/50 shadow-lg">
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-xs font-bold text-slate-500 dark:text-blue-400 uppercase tracking-wider">Select Course</h3>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-3 space-y-3">
                        <button 
                            v-for="course in courses" 
                            :key="course.id"
                            @click="selectCourse(course.id)"
                            class="w-full text-left p-3 rounded-xl transition-all duration-200 flex items-center justify-between group border-4 shadow-sm"
                            :class="selectedCourseId === course.id 
                                ? 'bg-blue-50 dark:bg-blue-50 border-blue-600 shadow-md ring-2 ring-blue-500/20' 
                                : 'bg-white dark:bg-slate-700 border-transparent hover:border-blue-600 hover:shadow-lg'"
                        >
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-10 h-10 rounded-lg bg-slate-200 dark:bg-slate-900 shrink-0 overflow-hidden relative border border-slate-300 dark:border-slate-600">
                                    <img v-if="course.thumbnail && !imageErrors[course.id]" 
                                         :src="course.thumbnail" 
                                         @error="handleImageError(course.id)"
                                         class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                                        <span class="text-[8px] text-slate-400 dark:text-slate-500 font-bold">IMG</span>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <span class="block truncate font-extrabold text-sm"
                                          :class="selectedCourseId === course.id ? 'text-blue-900' : 'text-slate-700 dark:text-white'">
                                        {{ course.title }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                            {{ countUpcoming(course.assignments) }} Upcoming
                                        </span>
                                        
                                        <span v-if="course.ungraded_count > 0" 
                                              class="bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full animate-pulse shadow-sm">
                                            {{ course.ungraded_count }} GRADE
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <main class="flex-1 bg-white dark:bg-slate-800 rounded-xl flex flex-col overflow-hidden border border-slate-200 dark:border-slate-700/50 shadow-lg">
                    
                    <div v-if="selectedCourse" class="flex flex-col h-full">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/30">
                            <div class="flex gap-1 bg-white dark:bg-slate-900 p-1 rounded-lg border border-slate-200 dark:border-slate-700/50">
                                <button 
                                    @click="activeTab = 'upcoming'"
                                    class="px-4 py-1.5 rounded-md text-xs font-bold transition-all"
                                    :class="activeTab === 'upcoming' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                >
                                    Upcoming
                                </button>
                                <button 
                                    @click="activeTab = 'past'"
                                    class="px-4 py-1.5 rounded-md text-xs font-bold transition-all"
                                    :class="activeTab === 'past' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                >
                                    Past Due
                                </button>
                            </div>

                            <Link :href="route('teacher.assignments.create', { course: selectedCourseId, source: 'global' })" 
                                  class="text-white bg-blue-600 hover:bg-blue-500 px-3 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 border-2 border-transparent hover:border-white shadow-sm">
                                + New Assignment
                            </Link>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 dark:bg-transparent">
                            
                            <div v-if="filteredAssignments.length > 0" class="space-y-3">
                                
                                <Link v-for="assignment in filteredAssignments" :key="assignment.id" 
                                     :href="route('teacher.assignments.show', { assignment: assignment.id, source: 'global' })"
                                     class="group bg-white dark:bg-white rounded-xl p-3 border-4 border-transparent hover:border-blue-600 shadow-sm hover:shadow-lg transition-all duration-200 cursor-pointer flex items-center gap-3">
                                    
                                    <div class="shrink-0 p-2 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    </div>

                                    <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                        <h4 class="text-sm font-extrabold text-slate-900 whitespace-nowrap">
                                            {{ assignment.title }}
                                        </h4>
                                        <span class="hidden sm:inline text-xs text-slate-400 font-medium truncate max-w-[280px] group-hover:text-slate-500 transition">
                                            - {{ assignment.description || 'No description provided' }}
                                        </span>
                                        <p class="sm:hidden text-xs text-slate-400 font-medium truncate">
                                            {{ assignment.description }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-3 shrink-0">
                                        <div class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg text-[10px] font-bold border border-slate-200 whitespace-nowrap">
                                            {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString() : 'No Date' }}
                                        </div>
                                        
                                        <span class="text-blue-600 font-black text-xs whitespace-nowrap w-12 text-right">
                                            {{ assignment.points }} pts
                                        </span>

                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </div>

                                </Link>
                            </div>

                            <div v-else class="flex flex-col items-center justify-center h-48 text-slate-500">
                                <p class="text-xs font-bold">No {{ activeTab }} assignments found.</p>
                            </div>

                        </div>
                    </div>

                    <div v-else class="flex items-center justify-center h-full text-slate-500 font-bold">
                        <p>Select a course from the sidebar to view assignments.</p>
                    </div>

                </main>
            </div>
        </div>
    </AuthenticatedLayout>
</template>