<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ courses: Array });
</script>

<template>
    <Head title="Course Oversight" />
    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Global Courses</h1>
            <p class="text-slate-500 text-xs">Monitoring all active academic offerings.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="course in courses" :key="course.id" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded uppercase text-slate-500">{{ course.difficulty_level }}</span>
                    <span class="text-[10px] font-bold text-blue-600">{{ course.enrollments_count }} Students</span>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white mb-1">{{ course.title }}</h3>
                <p class="text-[11px] text-slate-500 mb-3 line-clamp-2">{{ course.description }}</p>
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                    <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center text-[8px] text-white font-bold">T</div>
                    <span class="text-[10px] text-slate-400 font-medium">Instructor: {{ course.teacher.name }}</span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>