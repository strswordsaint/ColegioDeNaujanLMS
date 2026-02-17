<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array
});

const searchQuery = ref('');
const activeTab = ref('student');

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        const matchesTab = activeTab.value === 'pending' 
            ? user.role === 'pending_teacher' 
            : user.role === activeTab.value;
            
        const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             user.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             (user.school_id && user.school_id.toLowerCase().includes(searchQuery.value.toLowerCase()));
                             
        return matchesTab && matchesSearch;
    });
});

const toggleStatus = (id) => {
    if (confirm('Change account status?')) {
        router.patch(route('admin.users.toggle-status', id));
    }
};

const approveTeacher = (id) => {
    if (confirm('Approve this teacher account?')) {
        router.patch(route('admin.users.approve-teacher', id));
    }
};
const rejectTeacher = (id) => {
    if (confirm('Are you sure you want to REJECT and DELETE this teacher application? This cannot be undone.')) {
        router.delete(route('admin.users.reject-teacher', id));
    }
};

const tabs = [
    { id: 'student', name: 'Students', icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
    { id: 'teacher', name: 'Teachers', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { id: 'pending', name: 'Pending', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }
];
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto px-2 sm:px-4">
            
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">User Management</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">Control system access</p>
                </div>
                
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search by name, email, or ID..." 
                        class="block w-full pl-10 pr-4 py-2 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all text-slate-900 dark:text-slate-100 placeholder-slate-400 shadow-sm"
                    />
                </div>
            </div>

            <div class="flex gap-1 mb-6 border-b border-slate-200 dark:border-slate-800 overflow-x-auto no-scrollbar">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        activeTab === tab.id 
                        ? 'border-blue-600 text-blue-700 dark:text-blue-400 bg-blue-50/30 dark:bg-blue-900/10 border-b-4' 
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                    ]"
                    class="flex items-center gap-2 px-5 py-3 text-xs font-bold transition-all whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                    </svg>
                    {{ tab.name }}
                    <span v-if="tab.id === 'pending' && users.filter(u => u.role === 'pending_teacher').length > 0" 
                          class="ml-2 px-1.5 py-0.5 bg-amber-500 text-white text-[10px] rounded-full animate-pulse">
                        {{ users.filter(u => u.role === 'pending_teacher').length }}
                    </span>
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-300 uppercase font-bold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="px-6 py-4">User Details</th>
                                <th class="px-6 py-4">Status / ID</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-slate-50">{{ user.name }}</div>
                                    <div class="text-slate-500 dark:text-slate-400 font-medium">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-700 dark:text-slate-300 font-mono mb-1.5">{{ user.school_id || 'N/A' }}</div>
                                    <span :class="[
                                        user.role === 'suspended' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    ]" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tight">
                                        {{ user.role === 'suspended' ? 'Suspended' : 'Active' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <template v-if="user.role === 'pending_teacher'">
                                            <button 
                                                @click="approveTeacher(user.id)" 
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-sm transition"
                                            >
                                                Approve
                                            </button>
                                            <button 
                                                @click="rejectTeacher(user.id)" 
                                                class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-sm transition"
                                            >
                                                Reject
                                            </button>
                                        </template>

                                        <button 
                                            v-else
                                            @click="toggleStatus(user.id)" 
                                            :class="user.role === 'suspended' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-red-600 hover:bg-red-700'"
                                            class="text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-sm transition"
                                        >
                                            {{ user.role === 'suspended' ? 'Unsuspend' : 'Suspend' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 italic">
                                    No records found matching your selection.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>