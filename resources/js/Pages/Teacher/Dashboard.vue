<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    chart_data: Object,
    grading_queue: Array,
});
</script>

<template>
    <Head title="Overview" />

    <AuthenticatedLayout>
        <div class="flex justify-between items-end mb-4">
            <div>
                <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
                <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-0.5">Classroom performance overview.</p>
            </div>
            <div class="hidden md:block">
                <span class="text-[10px] font-mono bg-white dark:bg-slate-800 text-slate-600 dark:text-blue-200 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3 h-3 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200 group shadow-sm">
                <div class="text-slate-500 dark:text-blue-300/80 text-[9px] font-bold uppercase tracking-wider mb-1">Active Courses</div>
                <div class="text-2xl font-black text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ stats.active_courses }}</div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200 group shadow-sm">
                <div class="text-slate-500 dark:text-blue-300/80 text-[9px] font-bold uppercase tracking-wider mb-1">Students</div>
                <div class="text-2xl font-black text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ stats.total_students }}</div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200 group shadow-sm">
                <div class="text-slate-500 dark:text-blue-300/80 text-[9px] font-bold uppercase tracking-wider mb-1">To Grade</div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-black text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ stats.pending_submissions }}</span>
                    <span v-if="stats.pending_submissions > 0" class="text-[9px] bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded border border-red-200 dark:border-red-800">Urgent</span>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200 group shadow-sm">
                <div class="text-slate-500 dark:text-blue-300/80 text-[9px] font-bold uppercase tracking-wider mb-1">Avg. Grade</div>
                <div class="text-2xl font-black text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ stats.average_grade }}%</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-4 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-sm text-slate-800 dark:text-white">Activity</h3>
                    <div class="flex gap-3 text-[9px]">
                        <span class="flex items-center gap-1 text-slate-500 dark:text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Received</span>
                        <span class="flex items-center gap-1 text-slate-500 dark:text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Graded</span>
                    </div>
                </div>
                
                <div class="relative h-32 w-full flex items-end justify-between px-2">
                    <div v-for="(val, index) in chart_data.received" :key="index" class="relative flex flex-col items-center group w-full mx-1">
                        <div class="w-full flex gap-0.5 items-end justify-center h-24">
                            <div class="w-2.5 bg-blue-500 dark:bg-blue-600 rounded-t-[1px] transition-all duration-300 group-hover:bg-blue-400" :style="`height: ${val * 1.5}px`"></div>
                            <div class="w-2.5 bg-emerald-500 dark:bg-emerald-600 rounded-t-[1px] transition-all duration-300 group-hover:bg-emerald-400" :style="`height: ${chart_data.graded[index] * 1.5}px`"></div>
                        </div>
                        <div class="mt-2 text-[9px] text-slate-400 dark:text-slate-500 font-bold">{{ chart_data.labels[index] }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border border-slate-200 dark:border-slate-700 flex flex-col shadow-sm">
                <h3 class="font-bold text-sm text-slate-800 dark:text-white mb-3">Priority Grading</h3>
                <div class="flex-1 space-y-2 overflow-y-auto max-h-48">
                    <div v-for="item in grading_queue" :key="item.id" 
                         class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700 hover:border-blue-400 transition-colors cursor-pointer group">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-1.5 h-1.5 rounded-full shrink-0" :class="item.priority === 'high' ? 'bg-red-500' : 'bg-amber-500'"></div>
                            <div class="min-w-0">
                                <div class="text-[10px] font-bold text-slate-700 dark:text-slate-200 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ item.task }}</div>
                                <div class="text-[9px] text-slate-400 dark:text-slate-500 truncate">{{ item.course }}</div>
                            </div>
                        </div>
                        <div class="text-[9px] font-mono bg-white dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            {{ item.count }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>