<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AIChatWidget from '@/Components/AIChatWidget.vue'; // <--- 1. Imported Here

const showingNavigationDropdown = ref(false);
const user = usePage().props.auth.user;
const isDark = ref(false);

// --- THEME LOGIC ---
onMounted(() => {
    if (document.documentElement.classList.contains('dark')) {
        isDark.value = true;
    }
});

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// --- NAVIGATION ICONS ---
const icons = {
    dashboard: "M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z",
    courses: "M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z",
    assignments: "M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z",
    users: "M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-3.833-6.249c-.183 0-.366.013-.547.038a5.68 5.68 0 011.174 3.59c0 .748-.145 1.46-.412 2.112zM4 19.128a9.38 9.38 0 012.625.372 9.337 9.337 0 014.121-.952 4.125 4.125 0 01-3.833-6.249 4.125 4.125 0 01-.547-.038 5.68 5.68 0 001.174 3.59c0 .748.145 1.46.412 2.112zM12 11.25a3.375 3.375 0 100-6.75 3.375 3.375 0 000 6.75zM9 1.5a2.25 2.25 0 110 4.5 2.25 2.25 0 010-4.5zm6 0a2.25 2.25 0 110 4.5 2.25 2.25 0 010-4.5z",
};

const menus = computed(() => {
    if (user.role === 'teacher') {
        return [
            { name: 'Overview', route: 'teacher.dashboard', icon: icons.dashboard },
            { name: 'My Courses', route: 'teacher.courses.index', icon: icons.courses },
            { name: 'Assignments', route: 'teacher.assignments.index', icon: icons.assignments },
        ];
    }
    else if (user.role === 'student') {
        return [
            { name: 'Home', route: 'dashboard', icon: icons.dashboard },
            { name: 'My Courses', route: 'student.courses', icon: icons.courses }, 
            { name: 'Assignments', route: 'student.assignments', icon: icons.assignments },
        ];
    }
    else if (user.role === 'admin') {
        return [
            { name: 'Dashboard', route: 'admin.dashboard', icon: icons.dashboard },
            { name: 'Users', route: 'admin.users.index', icon: icons.users },
            { name: 'Courses', route: 'admin.courses.index', icon: icons.courses },
        ];
    }
    return [];
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-300 flex font-sans antialiased text-sm transition-colors duration-300">
        
        <aside class="hidden md:flex flex-col w-44 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 h-screen fixed z-20 transition-colors duration-300">
            <div class="h-14 flex items-center px-4 border-b border-slate-200 dark:border-slate-800">
                <span class="text-slate-900 dark:text-white font-bold text-base tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.221 69.17 69.17 0 00-2.658.813m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"></path></svg>
                    Learnify
                </span>
            </div>

            <nav class="flex-1 py-4 px-2 space-y-1">
                <Link v-for="item in menus" :key="item.route" 
                    :href="route(item.route)" 
                    :class="{ 
                        'bg-blue-50 text-blue-700 border-r-2 border-blue-600 dark:bg-blue-600/10 dark:text-blue-400 dark:border-blue-500': route().current(item.route),
                        'border-r-2 border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white dark:hover:border-blue-500': !route().current(item.route)
                    }"
                    class="flex items-center px-3 py-2 transition-all duration-200 group rounded-r-sm"
                >
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"></path></svg>
                    <span class="text-xs font-medium truncate">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-2 border-t border-slate-200 dark:border-slate-800 space-y-2">
                
                <button @click="toggleTheme" class="flex items-center gap-3 w-full p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white transition group">
                    <div class="w-7 h-7 flex items-center justify-center">
                        <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </div>
                    <span class="text-xs font-medium">{{ isDark ? 'Light Mode' : 'Dark Mode' }}</span>
                </button>

                <Link :href="route('profile.edit')" class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 p-2 rounded-lg transition group w-full" title="Edit Profile">
                    <div class="w-7 h-7 rounded bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white text-[10px] font-bold ring-1 ring-white/10 shrink-0">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-xs font-medium text-slate-700 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ user.name }}</p>
                    </div>
                </Link>
            </div>
        </aside>

        <div class="flex-1 md:ml-44 flex flex-col min-h-screen">
            <header class="md:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 h-14 flex items-center justify-between px-4 sticky top-0 z-10 transition-colors duration-300">
                <span class="text-slate-900 dark:text-white font-bold">Learnify</span>
                <div class="flex gap-4">
                    <button @click="toggleTheme" class="text-slate-500 dark:text-slate-400">
                        <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="text-slate-500 dark:text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"></path></svg>
                    </button>
                </div>
            </header>
            
            <main class="flex-1 p-4 md:p-6">
                <slot />
            </main>
        </div>

        <AIChatWidget />
    </div>
</template>