<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    upcoming: Array,
    announcements: Array,
    remedial: Array
});

const joinForm = useForm({ code: '' });

const quickJoin = (code) => {
    joinForm.code = code;
    joinForm.post(route('student.courses.join'), {
        onSuccess: () => joinForm.reset(),
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white leading-tight">
                    Hello, {{ $page.props.auth.user.name.split(' ')[0] }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Your learning overview</p>
            </div>
            
            <div class="flex gap-3">
                <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-3">
                    <div class="p-1.5 bg-white/20 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold opacity-80">Enrolled</p>
                        <p class="text-lg font-black leading-none">{{ stats.courses }}</p>
                    </div>
                </div>
                <div class="bg-purple-600 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-3">
                    <div class="p-1.5 bg-white/20 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold opacity-80">Pending</p>
                        <p class="text-lg font-black leading-none">{{ stats.pending }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="remedial && remedial.length > 0" class="mb-6 animate-fade-in">
            <div class="bg-red-50 dark:bg-red-900/10 border-l-4 border-red-500 rounded-r-lg p-3 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full text-red-600 dark:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wide">Action Required</h3>
                        <p class="text-[10px] text-red-600 dark:text-red-300">
                            Your average dropped in <b>{{ remedial[0].course_title }}</b>. AI Suggestion: {{ remedial[0].remedial_tip }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <Link v-if="remedial[0].remedial_type === 'lesson'" 
                          :href="route('student.courses.show', { course: remedial[0].course_id, tab: 'materials', target: remedial[0].id })" 
                          class="text-xs font-bold bg-white text-red-600 border border-red-200 px-3 py-1.5 rounded hover:bg-red-50 transition shadow-sm w-full sm:w-auto text-center flex items-center justify-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Review Lesson
                    </Link>
                    
                    <Link v-else 
                          :href="route('student.courses.show', { course: remedial[0].course_id, tab: 'assignments', target: remedial[0].id })" 
                          class="text-xs font-bold bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 transition shadow-sm w-full sm:w-auto text-center flex items-center justify-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Do Assignment
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <div class="lg:col-span-3 space-y-6">
                
                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-2 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">Upcoming Deadlines</h3>
                        <Link :href="route('student.assignments')" class="text-[10px] text-blue-600 font-bold hover:underline">View All</Link>
                    </div>
                    <div v-if="upcoming.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
                        <div v-for="task in upcoming" :key="task.id" class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="w-8 h-8 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex flex-col items-center justify-center shrink-0 border border-blue-100 dark:border-blue-900/30">
                                    <span class="text-[8px] font-bold uppercase leading-none">{{ new Date(task.due_date).toLocaleString('default', { month: 'short' }) }}</span>
                                    <span class="text-xs font-black leading-none">{{ new Date(task.due_date).getDate() }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ task.title }}</h4>
                                    <p class="text-[10px] text-slate-500 truncate">{{ task.course.title }}</p>
                                </div>
                            </div>
                            <Link :href="route('student.courses.show', { course: task.course_id, tab: 'assignments', target: task.id })" 
                                  class="shrink-0 text-[10px] font-bold text-slate-500 border border-slate-200 px-2 py-1 rounded hover:bg-slate-100 dark:border-slate-600 dark:text-slate-400 dark:hover:bg-slate-700">
                                Open
                            </Link>
                        </div>
                    </div>
                    <div v-else class="p-6 text-center flex flex-col items-center justify-center gap-2">
                         <svg class="w-8 h-8 text-green-400 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-xs text-slate-400">All caught up!</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                
                <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Join New Class
                    </h3>
                    <form @submit.prevent="joinForm.post(route('student.courses.join'), { onSuccess: () => joinForm.reset() })" class="flex flex-col gap-2">
                        <input v-model="joinForm.code" type="text" placeholder="CODE" class="w-full py-1.5 px-3 rounded border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-xs font-mono uppercase focus:ring-1 focus:ring-blue-500">
                        <button :disabled="joinForm.processing" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-1.5 rounded text-[10px] font-bold uppercase tracking-wide transition">
                            Join
                        </button>
                    </form>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-700/50 px-4 py-2 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">Updates</h3>
                    </div>
                    <div v-if="announcements.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
                        <div v-for="post in announcements" :key="post.id" class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-600 flex items-center justify-center text-[9px] font-bold text-slate-600 dark:text-slate-300">{{ post.user.name.charAt(0) }}</div>
                                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate">{{ post.user.name }}</span>
                            </div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-2 mb-1 leading-snug">{{ post.content }}</p>
                            <Link :href="route('student.courses.show', post.course_id)" class="text-[9px] text-blue-600 font-bold hover:underline">Read</Link>
                        </div>
                    </div>
                    <div v-else class="p-4 text-center">
                        <p class="text-[10px] text-slate-400 italic">No updates.</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>