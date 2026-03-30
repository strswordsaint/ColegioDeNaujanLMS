<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Users, BookOpen, GraduationCap, FileWarning, ArrowUpRight, CheckCircle2 } from 'lucide-vue-next';
import { computed } from 'vue';

// Chart.js imports
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    stats: Object,
    demographics: Object,
    enrollmentTrend: Object,
    recentCourses: Array
});

// Configure Bar Chart (Enrollments)
const barChartData = computed(() => ({
    labels: props.enrollmentTrend.labels,
    datasets: [
        {
            label: 'New Enrollments',
            backgroundColor: '#2563eb', // blue-600
            borderRadius: 4,
            data: props.enrollmentTrend.data
        }
    ]
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
};

// Configure Doughnut Chart (Demographics)
const doughnutChartData = computed(() => ({
    labels: props.demographics.labels,
    datasets: [
        {
            backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'], // Blue, Orange, Emerald
            borderWidth: 0,
            data: props.demographics.data
        }
    ]
}));

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '75%',
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 8, padding: 15, font: { size: 10 } } }
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="max-w-screen-2xl mx-auto flex flex-col h-full gap-3 sm:gap-4 pb-6">
            
            <div class="flex items-center justify-between pb-2 sm:pb-3 border-b border-slate-200 dark:border-slate-800 shrink-0 px-1 sm:px-0">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">Overview</h1>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-0.5">System Statistics & Metrics</p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 lg:gap-4 px-1 sm:px-0">
                
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                        <h3 class="text-[9px] sm:text-[10px] font-black text-slate-500 uppercase tracking-widest truncate mr-2">Users</h3>
                        <Users class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 shrink-0" />
                    </div>
                    <div>
                        <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.totalUsers }}</div>
                        <p class="text-[8px] sm:text-[9px] font-bold text-emerald-500 mt-1 sm:mt-1.5 flex items-center gap-0.5 truncate"><ArrowUpRight class="w-2.5 h-2.5 sm:w-3 sm:h-3"/> Active</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                        <h3 class="text-[9px] sm:text-[10px] font-black text-slate-500 uppercase tracking-widest truncate mr-2">Classes</h3>
                        <BookOpen class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 shrink-0" />
                    </div>
                    <div>
                        <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.totalCourses }}</div>
                        <p class="text-[8px] sm:text-[9px] font-bold text-blue-500 mt-1 sm:mt-1.5 truncate">Across all levels</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                        <h3 class="text-[9px] sm:text-[10px] font-black text-slate-500 uppercase tracking-widest truncate mr-2">Enrolls</h3>
                        <GraduationCap class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-400 shrink-0" />
                    </div>
                    <div>
                        <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.totalEnrollments }}</div>
                        <p class="text-[8px] sm:text-[9px] font-bold text-slate-400 mt-1 sm:mt-1.5 truncate">Total student seats</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                        <h3 class="text-[9px] sm:text-[10px] font-black text-slate-500 uppercase tracking-widest truncate mr-2">Materials</h3>
                        <FileWarning class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" :class="stats.pendingMaterials > 0 ? 'text-red-500' : 'text-slate-400'" />
                    </div>
                    <div>
                        <div class="text-lg sm:text-2xl font-black leading-none" :class="stats.pendingMaterials > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'">{{ stats.pendingMaterials }}</div>
                        <Link v-if="stats.pendingMaterials > 0" :href="route('admin.materials')" class="text-[8px] sm:text-[9px] font-bold text-red-500 hover:underline mt-1 sm:mt-1.5 flex items-center gap-0.5 truncate">Requires Review &rarr;</Link>
                        <p v-else class="text-[8px] sm:text-[9px] font-bold text-emerald-500 mt-1 sm:mt-1.5 flex items-center gap-1 truncate">All caught up!</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-3 lg:gap-4 px-1 sm:px-0 flex-1 min-h-0">
                
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 lg:col-span-2 flex flex-col">
                    <div class="mb-2 sm:mb-4">
                        <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white leading-tight">Enrollment Trend</h3>
                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Last 6 Months</p>
                    </div>
                    <div class="relative flex-1 min-h-[160px] sm:min-h-[250px]">
                        <Bar :data="barChartData" :options="barChartOptions" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-3 sm:p-4 flex flex-col">
                    <div class="mb-2 sm:mb-4 text-center">
                        <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white leading-tight">Demographics</h3>
                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">System Distribution</p>
                    </div>
                    <div class="relative flex-1 min-h-[160px] sm:min-h-[200px] flex items-center justify-center">
                        <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pb-4 sm:pb-6">
                            <span class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white leading-none">{{ stats.totalUsers }}</span>
                            <span class="text-[8px] sm:text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Total</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden lg:col-span-3 flex flex-col">
                    <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-white leading-tight">Recently Added Classes</h3>
                            <p class="text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Latest 5 created</p>
                        </div>
                        <Link :href="route('admin.courses.index')" class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-800 transition">View All</Link>
                    </div>
                    
                    <div class="divide-y divide-slate-100 dark:divide-slate-800 flex-1 overflow-y-auto">
                        <div v-for="course in recentCourses" :key="course.id" class="p-2 sm:p-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-black text-[9px] uppercase shrink-0">
                                    {{ course.title.substring(0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate leading-tight">{{ course.title }}</h4>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest truncate mt-0.5">Teacher: {{ course.teacher?.name || 'Unassigned' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[8px] sm:text-[9px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/50">{{ course.difficulty_level }}</span>
                            </div>
                        </div>
                        <div v-if="recentCourses.length === 0" class="p-6 flex flex-col items-center text-center text-slate-400">
                            <CheckCircle2 class="w-6 h-6 mb-2 text-slate-300 dark:text-slate-600" />
                            <span class="text-[10px] font-bold uppercase tracking-widest">No courses available yet.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
