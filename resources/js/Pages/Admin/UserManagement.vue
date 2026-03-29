<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array
});

const searchQuery = ref('');
const activeTab = ref('student');
const archiveSubTab = ref('student'); 
const isCreateModalOpen = ref(false);
const isSuspendModalOpen = ref(false);

const form = useForm({
    role: 'teacher',
    name: '',
    email: '',
    school_id: '',
    program: '',
    contact_number: '',
    password: '',
});

const suspendForm = useForm({
    user_id: null,
    reason: '',
});

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                             user.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                             (user.school_id && user.school_id.toLowerCase().includes(searchQuery.value.toLowerCase()));

        if (activeTab.value === 'archive') {
            return user.status === 'suspended' && user.role === archiveSubTab.value && matchesSearch;
        } else {
            return user.status === 'active' && user.role === activeTab.value && matchesSearch;
        }
    });
});

const confirmSuspend = (user) => {
    suspendForm.user_id = user.id;
    suspendForm.reason = '';
    isSuspendModalOpen.value = true;
};

const submitSuspend = () => {
    suspendForm.patch(route('admin.users.toggle-status', suspendForm.user_id), {
        onSuccess: () => {
            isSuspendModalOpen.value = false;
            suspendForm.reset();
        }
    });
};

const unsuspendUser = (id) => {
    if (confirm('Are you sure you want to reactivate this account?')) {
        router.patch(route('admin.users.toggle-status', id));
    }
};

const submitUser = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
            alert('User created! They have been sent an email to verify their address.');
        },
    });
};

const tabs = [
    { id: 'student', name: 'Students' },
    { id: 'teacher', name: 'Teachers' },
    { id: 'admin', name: 'Admins' },
    { id: 'archive', name: 'Archive' }
];

const subTabs = [
    { id: 'student', name: 'Suspended Students' },
    { id: 'teacher', name: 'Suspended Teachers' },
    { id: 'admin', name: 'Suspended Admins' }
];
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        
        <div class="mb-4 flex justify-between items-center max-w-7xl mx-auto px-4 sm:px-6">
             <div class="flex items-center gap-3">
                 <div>
                    <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white leading-tight">User Management</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-[9px] sm:text-[10px] uppercase font-bold tracking-wider">Control system access</p>
                 </div>
             </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row gap-4 md:gap-6 items-start">
            
            <aside class="w-full md:w-12 shrink-0 flex flex-row md:flex-col gap-3 sticky top-4 md:top-6 z-10 order-2 md:order-1">
                <button @click="isCreateModalOpen = true" class="group relative flex items-center justify-center w-10 h-10 md:w-12 md:h-12 bg-white dark:bg-slate-800 rounded-full border-2 border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm focus:outline-none shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span class="absolute bottom-full mb-2 md:bottom-auto md:left-full md:ml-3 md:mb-0 px-2 py-1 bg-slate-800 text-white text-[10px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Add New User</span>
                </button>
            </aside>

            <div class="flex-1 min-w-0 w-full order-1 md:order-2">
                
                <div class="relative w-full md:max-w-sm mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search by name, email, or ID..." 
                        class="block w-full pl-9 pr-3 py-1.5 text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all text-slate-900 dark:text-slate-100 placeholder-slate-400 shadow-sm"
                    />
                </div>

                <div class="flex gap-4 border-b border-slate-200 dark:border-slate-700 mb-4 overflow-x-auto no-scrollbar">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="pb-1.5 text-xs sm:text-sm font-bold border-b-2 transition-colors flex items-center gap-1.5 whitespace-nowrap" 
                        :class="activeTab === tab.id ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                    >
                        {{ tab.name }}
                    </button>
                </div>

                <div v-if="activeTab === 'archive'" class="flex flex-wrap gap-2 mb-3">
                    <button 
                        v-for="subTab in subTabs" 
                        :key="subTab.id"
                        @click="archiveSubTab = subTab.id"
                        :class="[
                            archiveSubTab === subTab.id 
                            ? 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800/50' 
                            : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'
                        ]"
                        class="px-3 py-1 text-[10px] font-bold rounded-full transition-all border shadow-sm"
                    >
                        {{ subTab.name }}
                    </button>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden mb-8">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            <thead class="text-[9px] sm:text-[10px] uppercase font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700">
                                <tr>
                                    <th class="px-4 py-3">User Details</th>
                                    <th class="px-4 py-3">Status / ID</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                    <td class="px-4 py-2.5">
                                        <div class="font-bold text-slate-900 dark:text-white truncate max-w-[120px] sm:max-w-xs">{{ user.name }}</div>
                                        <div class="text-[10px] sm:text-xs mt-0.5 truncate max-w-[120px] sm:max-w-xs">{{ user.email }}</div>
                                        <div v-if="user.status === 'suspended'" class="mt-1 text-[9px] text-red-600 dark:text-red-400 font-medium max-w-[120px] sm:max-w-xs truncate bg-red-50 dark:bg-red-900/10 px-1.5 py-0.5 rounded inline-block border border-red-100 dark:border-red-900/30">
                                            Reason: {{ user.suspension_reason }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-slate-700 dark:text-slate-300 font-mono text-[10px] sm:text-xs mb-1">{{ user.school_id || 'N/A' }}</div>
                                        <span :class="[
                                            user.status === 'suspended' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                        ]" class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-tight">
                                            {{ user.status === 'suspended' ? 'Suspended' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                                            <button v-if="user.status === 'suspended'" @click="unsuspendUser(user.id)" class="bg-blue-600 hover:bg-blue-500 text-white px-2.5 py-1 rounded text-[10px] font-bold shadow-sm transition">
                                                Unsuspend
                                            </button>
                                            
                                            <button v-else @click="confirmSuspend(user)" class="text-red-500 hover:text-red-700 text-[10px] font-bold bg-red-50 dark:bg-red-900/20 px-2.5 py-1 rounded border border-red-100 dark:border-red-900/30 transition">
                                                Suspend
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 italic text-xs">
                                        No records found matching your selection.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="isCreateModalOpen" @close="isCreateModalOpen = false">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">Create New Account</h2>
                
                <div class="flex gap-4 border-b border-slate-200 dark:border-slate-700 mb-4 overflow-x-auto no-scrollbar">
                    <button type="button" @click="form.role = 'teacher'" :class="{'border-blue-600 text-blue-600 dark:text-blue-400': form.role === 'teacher', 'border-transparent text-slate-500': form.role !== 'teacher'}" class="pb-1.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap">Teacher</button>
                    <button type="button" @click="form.role = 'student'" :class="{'border-blue-600 text-blue-600 dark:text-blue-400': form.role === 'student', 'border-transparent text-slate-500': form.role !== 'student'}" class="pb-1.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap">Student</button>
                    <button type="button" @click="form.role = 'admin'" :class="{'border-blue-600 text-blue-600 dark:text-blue-400': form.role === 'admin', 'border-transparent text-slate-500': form.role !== 'admin'}" class="pb-1.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap">Admin</button>
                </div>

                <form @submit.prevent="submitUser" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="name" value="Full Name" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                            <input id="name" v-model="form.name" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" required />
                            <InputError :message="form.errors.name" class="mt-1 text-[10px]" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email Address" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                            <input id="email" v-model="form.email" type="email" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" required />
                            <InputError :message="form.errors.email" class="mt-1 text-[10px]" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" v-if="form.role !== 'admin'">
                        <div>
                            <InputLabel for="school_id" value="ID Number" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                            <input id="school_id" v-model="form.school_id" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" :required="form.role !== 'admin'" />
                            <InputError :message="form.errors.school_id" class="mt-1 text-[10px]" />
                        </div>
                        <div>
                            <InputLabel for="contact_number" value="Mobile Number" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                            <input id="contact_number" v-model="form.contact_number" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" />
                            <InputError :message="form.errors.contact_number" class="mt-1 text-[10px]" />
                        </div>
                    </div>

                    <div v-if="form.role === 'student'">
                        <InputLabel for="program" value="Program / Course" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                        <input id="program" v-model="form.program" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" placeholder="e.g. BSIT" required />
                        <InputError :message="form.errors.program" class="mt-1 text-[10px]" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Temporary Password" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                        <input id="password" v-model="form.password" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500" placeholder="Give this to the user to log in" required />
                        <InputError :message="form.errors.password" class="mt-1 text-[10px]" />
                        <p class="text-[9px] text-slate-500 mt-1">They will be emailed a link to verify their account before they can use this password.</p>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="isCreateModalOpen = false" class="text-xs text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700">Cancel</button>
                        <button :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded text-xs font-bold shadow-sm">Create Account</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="isSuspendModalOpen" @close="isSuspendModalOpen = false">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h2 class="text-base font-bold text-slate-900 dark:text-white mb-2">Suspend Account</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Please provide a reason for suspending this account. This reason will be shown to the user if they attempt to log in.</p>
                
                <form @submit.prevent="submitSuspend" class="space-y-3">
                    <div>
                        <InputLabel for="reason" value="Suspension Reason" class="text-[9px] font-bold uppercase text-slate-500 mb-1" />
                        <textarea 
                            id="reason" 
                            v-model="suspendForm.reason" 
                            rows="3"
                            class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-xs focus:ring-blue-500 resize-none" 
                            placeholder="e.g. Violation of school policy regarding assignment submission..."
                            required
                        ></textarea>
                        <InputError :message="suspendForm.errors.reason" class="mt-1 text-[10px]" />
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="isSuspendModalOpen = false" class="text-xs text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700">Cancel</button>
                        <button :disabled="suspendForm.processing" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded text-xs font-bold shadow-sm">Suspend Account</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>