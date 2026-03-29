<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import AIChatWidget from '@/Components/AIChatWidget.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const showingNavigationDropdown = ref(false);
const showingNotifications = ref(false); // Controls the floating dropdown
const page = usePage();
const user = page.props.auth.user;
const isDark = ref(false);

// --- NOTIFICATION SYSTEM & SOUND LOGIC ---
const notifications = computed(() => page.props.auth.notifications || []);
const unreadCount = computed(() => page.props.auth.unread_count || 0);
const previousUnreadCount = ref(unreadCount.value);

watch(unreadCount, (newCount) => {
    if (newCount > previousUnreadCount.value) {
        const audio = new Audio('/sounds/notification.mp3');
        audio.play().catch(error => console.log('Auto-play prevented by browser interaction policy.'));
    }
    previousUnreadCount.value = newCount;
}, { immediate: true });
// ----------------------------------------------

// TOAST NOTIFICATION LOGIC
const toast = ref(null);
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.success) showToast(newFlash.success, 'success');
    else if (newFlash?.error) showToast(newFlash.error, 'error');
    else if (newFlash?.status) showToast(newFlash.status, 'status');
}, { deep: true, immediate: true });

const showToast = (message, type) => {
    toast.value = { message, type };
    setTimeout(() => toast.value = null, 4000);
};

// THEME LOGIC
onMounted(() => {
    const theme = localStorage.getItem('theme');
    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
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

// --- SECURITY TOGGLE LOGIC ---
const confirmingToggle = ref(false);
const toggleForm = useForm({ password: '' });

// We define this here so it can be called from MaterialApproval.vue via $parent
const confirmToggle = () => {
    // Only require password when turning OFF (current state is requireApproval: true)
    if (page.props.requireApproval) {
        confirmingToggle.value = true;
    } else {
        submitToggle();
    }
};

const submitToggle = () => {
    toggleForm.post(route('admin.settings.material-approval'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingToggle.value = false;
            toggleForm.reset();
        },
    });
};

// NAVIGATION ICONS (RESTORED ALL PATHS)
const icons = {
    dashboard: "M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z",
    courses: "M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z",
    assignments: "M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z",
    users: "M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-3.833-6.249c-.183 0-.366.013-.547.038a5.68 5.68 0 011.174 3.59c0 .748-.145 1.46-.412 2.112zM4 19.128a9.38 9.38 0 012.625.372 9.337 9.337 0 014.121-.952 4.125 4.125 0 01-3.833-6.249 4.125 4.125 0 01-.547-.038 5.68 5.68 0 001.174 3.59c0 .748.145 1.46.412 2.112zM12 11.25a3.375 3.375 0 100-6.75 3.375 3.375 0 000 6.75zM9 1.5a2.25 2.25 0 110 4.5 2.25 2.25 0 010-4.5zm6 0a2.25 2.25 0 110 4.5 2.25 2.25 0 010-4.5z",
    calendar: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
    gradebook: "M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z",
};

const menus = computed(() => {
    if (user.role === 'teacher') {
        return [
            { name: 'Overview', route: 'teacher.dashboard', icon: icons.dashboard },
            { name: 'My Courses', route: 'teacher.courses.index', icon: icons.courses },
            { name: 'Gradebook', route: 'teacher.gradebook.index', icon: icons.gradebook },
            { name: 'Assignments', route: 'teacher.assignments.index', icon: icons.assignments },
            { name: 'Calendar', route: 'calendar.index', icon: icons.calendar },
        ];
    }
    else if (user.role === 'student') {
        return [
            { name: 'Home', route: 'dashboard', icon: icons.dashboard },
            { name: 'My Courses', route: 'student.courses', icon: icons.courses }, 
            { name: 'Assignments', route: 'student.assignments', icon: icons.assignments },
            { name: 'Calendar', route: 'calendar.index', icon: icons.calendar },
        ];
    }
    else if (user.role === 'admin') {
        return [
            { name: 'Dashboard', route: 'admin.dashboard', icon: icons.dashboard },
            { name: 'Users', route: 'admin.users.index', icon: icons.users },
            { name: 'Courses', route: 'admin.courses.index', icon: icons.courses },
            { name: 'Material Approval', route: 'admin.materials', icon: icons.assignments },
            { name: 'Calendar', route: 'calendar.index', icon: icons.calendar },
        ];
    }
    return [];
});

defineExpose({ confirmToggle });
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-300 flex font-sans antialiased text-sm transition-colors duration-300 relative pb-20 md:pb-0">
        
        <div v-if="toast" class="fixed top-4 right-4 z-[70] transition-all duration-300 shadow-lg rounded-lg overflow-hidden border"
             :class="{'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300': toast.type === 'success',
                      'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300': toast.type === 'error',
                      'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300': toast.type === 'status'}">
            <div class="px-4 py-3 flex items-center gap-3">
                <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <svg v-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="font-medium text-sm">{{ toast.message }}</p>
                <button @click="toast = null" class="ml-4 opacity-50 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
        </div>

        <div v-if="showingNotifications" @click="showingNotifications = false" class="fixed inset-0 z-[55] bg-transparent"></div>

        <transition 
            enter-active-class="transition ease-out duration-200 transform" 
            enter-from-class="opacity-0 scale-95 translate-y-2" 
            enter-to-class="opacity-100 scale-100 translate-y-0" 
            leave-active-class="transition ease-in duration-150 transform" 
            leave-from-class="opacity-100 scale-100 translate-y-0" 
            leave-to-class="opacity-0 scale-95 translate-y-2"
        >
            <div v-if="showingNotifications" class="fixed bottom-24 right-4 md:right-20 z-[60] w-[calc(100vw-2rem)] sm:w-80 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden max-h-[80vh] origin-bottom-right">
                <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-widest text-[10px] flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        Notifications
                    </h3>
                    <span v-if="unreadCount > 0" class="bg-red-500 text-white px-1.5 py-0.5 rounded text-[8px] font-bold">{{ unreadCount }} New</span>
                </div>
                
                <div class="flex-1 overflow-y-auto p-2 space-y-1.5">
                    <div v-if="notifications.length > 0">
                        <Link v-for="notif in notifications" :key="notif.id" :href="notif.data.url" @click="showingNotifications = false" 
                              class="block p-3 bg-white dark:bg-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/80 transition group relative overflow-hidden">
                            <div v-if="!notif.read_at" class="absolute left-0 top-2 bottom-2 w-1 rounded-r bg-blue-500"></div>
                            
                            <h4 class="text-[10px] font-black uppercase tracking-widest mb-1 flex items-center gap-1.5" :class="notif.data.color || 'text-blue-500'">
                                <svg v-if="notif.data.icon === 'star'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ notif.data.title }}
                            </h4>
                            <p class="text-[10px] text-slate-600 dark:text-slate-400 leading-snug ml-1.5">{{ notif.data.message }}</p>
                            <span class="text-[8px] font-bold uppercase tracking-wider text-slate-400 mt-1.5 block ml-1.5">{{ new Date(notif.created_at).toLocaleDateString() }}</span>
                        </Link>
                    </div>
                    <div v-else class="text-center py-8">
                        <div class="w-10 h-10 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">All caught up!</p>
                    </div>
                </div>
            </div>
        </transition>

        <aside class="hidden md:flex flex-col w-44 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 h-screen fixed z-20 transition-colors duration-300">
            <div class="h-14 flex items-center px-4 border-b border-slate-200 dark:border-slate-800">
                <span class="text-slate-900 dark:text-white font-bold text-base tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.221 69.17 69.17 0 00-2.658.813m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"></path></svg>
                    CDN LMS
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

                <Link :href="route('profile.edit')" class="flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 p-2 rounded-lg transition group w-full">
                    <div class="w-7 h-7 rounded bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white text-[10px] font-bold ring-1 ring-white/10 shrink-0">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-xs font-medium text-slate-700 dark:text-white truncate">{{ user.name }}</p>
                    </div>
                </Link>
            </div>
        </aside>

        <div class="flex-1 md:ml-44 flex flex-col min-h-screen">
            <header class="md:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 h-12 flex items-center justify-between px-4 sticky top-0 z-30 transition-colors duration-300">
                <span class="text-slate-900 dark:text-white font-black text-sm tracking-tight flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.499 5.221 69.17 69.17 0 00-2.658.813"></path></svg>
                    CDN LMS
                </span>
                
                <button @click="showingNotifications = !showingNotifications" class="relative p-2 text-slate-500 dark:text-slate-400 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white dark:border-slate-900 animate-pulse"></span>
                </button>
            </header>
            
            <main class="flex-1 p-3 md:p-6 pt-16 md:pt-6" @click="showingNavigationDropdown = false">
                <slot />
            </main>
        </div>

        <div class="md:hidden fixed bottom-4 left-1/2 -translate-x-1/2 w-[92%] max-w-[360px] bg-slate-900/95 dark:bg-slate-800/95 backdrop-blur-xl border border-slate-700/50 rounded-full shadow-2xl z-50 flex items-center justify-around p-1.5">
            <Link v-for="item in menus.slice(0, 4)" :key="item.route" 
                :href="route(item.route)"
                class="relative p-2.5 rounded-full transition-all duration-300"
                :class="route().current(item.route) ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"></path></svg>
            </Link>
            
            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2.5 rounded-full text-slate-400 hover:text-white transition-all duration-300" :class="{'bg-slate-700 text-white': showingNavigationDropdown}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"></path></svg>
            </button>
        </div>

        <div class="hidden md:block fixed z-40 bottom-6 right-20 transition-all duration-300">
            <button @click="showingNotifications = !showingNotifications" 
                    class="relative p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full shadow-lg hover:shadow-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition text-slate-600 dark:text-slate-300 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span v-if="unreadCount > 0" class="absolute top-0 right-0 w-3.5 h-3.5 bg-red-500 rounded-full border-2 border-white dark:border-slate-800 animate-pulse"></span>
            </button>
        </div>

        <AIChatWidget />

        <Modal :show="confirmingToggle" @close="confirmingToggle = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Security Confirmation</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Please enter your password to confirm deactivating the material approval system.</p>
                <div class="mt-6">
                    <InputLabel for="password" value="Password" class="sr-only" />
                    <TextInput 
                        v-model="toggleForm.password" 
                        type="password" 
                        class="mt-1 block w-full" 
                        placeholder="Admin Password" 
                        @keyup.enter="submitToggle" 
                    />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingToggle = false">Cancel</SecondaryButton>
                    <DangerButton @click="submitToggle" :disabled="toggleForm.processing">Confirm Deactivation</DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>