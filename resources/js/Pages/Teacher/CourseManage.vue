<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const props = defineProps({ course: Object });
const page = usePage();
const currentUser = page.props.auth.user;

// Access global requireApproval setting from Inertia shared props
const requireApproval = computed(() => page.props.requireApproval ?? true);

const activeTab = ref('students');
const studentSubTab = ref('accepted');
const studentSort = ref('alpha_asc'); 
const assignmentFilter = ref('upcoming');

const showLessonModal = ref(false);
const showAnnouncementModal = ref(false);
const showAssignmentModal = ref(false);

// --- RESUBMIT MODAL LOGIC ---
const showResubmitModal = ref(false);
const lessonToResubmit = ref(null);

const formResubmit = useForm({
    file: null,
});

const openResubmitModal = (lesson) => {
    lessonToResubmit.value = lesson;
    formResubmit.clearErrors();
    formResubmit.reset();
    showResubmitModal.value = true;
};

const submitResubmit = () => {
    // Uses POST because file uploads via Inertia require POST
    // FIXED: Route name is now 'teacher.lessons.resubmit'
    formResubmit.post(route('teacher.lessons.resubmit', lessonToResubmit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showResubmitModal.value = false;
            lessonToResubmit.value = null;
        }
    });
};

// Forms
const formLesson = useForm({ 
    title: '', 
    file: null,
    available_from: '',
    available_until: ''
});

const formAnnouncement = useForm({ 
    title: '',
    video_link: '',
    content: '' 
});

const formComment = useForm({ content: '' });

const formAssignment = useForm({
    title: '',
    type: 'assignment',
    description: '',
    points: 100,
    due_date: '',
    files: [] 
});

const materialFilter = ref('active');
const activeMaterials = computed(() => {
    const now = new Date();
    return props.course.lessons?.filter(l => 
        l.approval_status !== 'approved' || 
        (!l.available_until || new Date(l.available_until) > now)
    ) || [];
});

const archivedMaterials = computed(() => {
    const now = new Date();
    return props.course.lessons?.filter(l => 
        l.approval_status === 'approved' && 
        l.available_until && new Date(l.available_until) <= now
    ) || [];
});

const displayedMaterials = computed(() => materialFilter.value === 'active' ? activeMaterials.value : archivedMaterials.value);

const requestUnarchive = (id) => {
    if(confirm('Request admin to unarchive this material?')) {
        router.patch(route('teacher.lessons.unarchive', id), {}, { preserveScroll: true });
    }
};

// Helper to extract YouTube ID
const getYouTubeEmbedUrl = (url) => {
    if (!url) return null;
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? `https://www.youtube.com/embed/${match[2]}` : null;
};

// --- STUDENTS DATA & RANKING LOGIC ---
const pendingStudents = computed(() => props.course.enrollments ? props.course.enrollments.filter(e => e.status === 'pending') : []);
const approvedStudentsRaw = computed(() => props.course.enrollments ? props.course.enrollments.filter(e => e.status === 'approved') : []);

const processedStudents = computed(() => {
    if (!approvedStudentsRaw.value.length) return [];

    // 1. Calculate scores for every student
    let studentsWithScores = approvedStudentsRaw.value.map(enrollment => {
        let totalScore = 0;
        let totalPossible = 0;
        
        if (props.course.assignments) {
            props.course.assignments.forEach(assignment => {
                totalPossible += parseInt(assignment.points || 0);
                // Find submission for this specific student
                const sub = assignment.submissions ? assignment.submissions.find(s => s.user_id === enrollment.user_id) : null;
                if (sub && sub.grade !== null) {
                    totalScore += parseFloat(sub.grade);
                }
            });
        }
        
        return {
            ...enrollment,
            totalScore,
            totalPossible,
            percentage: totalPossible > 0 ? ((totalScore / totalPossible) * 100).toFixed(1) : 0
        };
    });

    // 2. Sort to determine their Rank (Highest score is Rank 1)
    let sortedForRank = [...studentsWithScores].sort((a, b) => b.totalScore - a.totalScore);
    sortedForRank.forEach((s, index) => s.rank = index + 1);

    // 3. Return the array sorted based on the dropdown selection
    return studentsWithScores.sort((a, b) => {
        if (studentSort.value === 'alpha_asc') return a.user.name.localeCompare(b.user.name);
        if (studentSort.value === 'rank_desc') return b.totalScore - a.totalScore; // Rank 1 to Bottom
        if (studentSort.value === 'rank_asc') return a.totalScore - b.totalScore; // Bottom to Rank 1
        return 0;
    });
});

const getRankClass = (rank) => {
    if (rank === 1) return 'bg-yellow-100 text-yellow-700 border-yellow-300 border shadow-sm'; // Gold
    if (rank === 2) return 'bg-slate-200 text-slate-700 border-slate-300 border shadow-sm'; // Silver
    if (rank === 3) return 'bg-orange-100 text-orange-800 border-orange-300 border shadow-sm'; // Bronze
    return 'bg-blue-50 text-blue-600 border border-blue-100'; // Standard
};

// --- ASSIGNMENTS FILTER LOGIC ---
const filteredAssignments = computed(() => {
    const now = new Date();
    if (!props.course.assignments) return [];
    
    return props.course.assignments.filter(a => {
        const dueDate = a.due_date ? new Date(a.due_date) : null;
        const isPastDue = dueDate && dueDate < now;

        if (assignmentFilter.value === 'upcoming') return !isPastDue;
        if (assignmentFilter.value === 'past_due') return isPastDue;
        if (assignmentFilter.value === 'completed') return isPastDue; // On Teacher side, let's group closed/past assignments here
        
        return true;
    });
});

// Methods
const approveStudent = (userId) => router.patch(route('teacher.courses.enrollments.approve', { course: props.course.id, user: userId }), {}, { preserveScroll: true });
const removeStudent = (userId) => { if(confirm('Remove this student from the class?')) router.delete(route('teacher.courses.enrollments.destroy', { course: props.course.id, user: userId }), { preserveScroll: true }); };
const toggleComments = (announcement) => { announcement.showComments = !announcement.showComments; };
const copyCode = () => { navigator.clipboard.writeText(props.course.enrollment_code); alert('Copied!'); };
const deleteItem = (url) => { if (confirm('Are you sure?')) router.delete(url, { preserveScroll: true }); };

const submitAnnouncement = () => formAnnouncement.post(route('teacher.announcements.store', props.course.id), { onSuccess: () => { showAnnouncementModal.value = false; formAnnouncement.reset(); }});
const submitComment = (announcementId) => formComment.post(route('comments.store', announcementId), { onSuccess: () => { formComment.reset(); const post = props.course.announcements.find(a => a.id === announcementId); if(post) post.showComments = true; }, preserveScroll: true });
const submitAssignment = () => formAssignment.post(route('teacher.assignments.store', props.course.id), { onSuccess: () => { showAssignmentModal.value = false; formAssignment.reset(); }});
const submitLesson = () => formLesson.post(route('teacher.lessons.store', props.course.id), { onSuccess: () => { showLessonModal.value = false; formLesson.reset(); }});
</script>

<template>
    <Head :title="`Manage: ${course.title}`" />

    <AuthenticatedLayout>
        <div class="mb-4 flex justify-between items-center">
             <div class="flex items-center gap-2">
                 <h1 class="text-lg font-bold text-slate-900 dark:text-white">{{ course.title }}</h1>
                 <span class="text-[9px] font-mono bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded text-slate-600 dark:text-slate-300 cursor-pointer hover:bg-slate-300 transition" @click="copyCode" title="Click to copy code">{{ course.enrollment_code }}</span>
             </div>
             <Link :href="route('teacher.courses.edit', { course: course.id, source: 'manage' })" class="text-[10px] font-bold text-blue-600 hover:underline">Settings</Link>
        </div>

        <div class="flex gap-4 items-start">
            
            <aside class="w-10 shrink-0 flex flex-col gap-3 sticky top-4 z-30">
                <button @click="showAnnouncementModal = true" class="group relative flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700 text-red-600 hover:border-red-600 hover:bg-red-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-slate-800 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Post Announcement</span>
                </button>

                <button @click="showAssignmentModal = true" class="group relative flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700 text-blue-600 hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-slate-800 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Create Assignment</span>
                </button>

                <button @click="showLessonModal = true" class="group relative flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700 text-yellow-600 hover:border-yellow-500 hover:bg-yellow-50 dark:hover:bg-slate-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-slate-800 text-white text-[9px] font-bold rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-lg">Upload Materials</span>
                </button>
            </aside>

            <div class="flex-1 min-w-0 w-full">
                
                <div class="sticky top-0 z-20 backdrop-blur-xl bg-white/80 dark:bg-slate-900/80 pt-4 pb-0 mb-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-4">
                        <button @click="activeTab = 'students'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors flex items-center gap-1.5" :class="activeTab === 'students' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">
                            Students <span v-if="pendingStudents.length" class="bg-red-500 text-white text-[8px] px-1 rounded-full">{{ pendingStudents.length }}</span>
                        </button>
                        <button @click="activeTab = 'announcements'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'announcements' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Announcements</button>
                        <button @click="activeTab = 'assignments'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'assignments' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Assignments</button>
                        <button @click="activeTab = 'materials'" class="pb-1.5 text-xs font-bold border-b-2 transition-colors" :class="activeTab === 'materials' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'">Materials</button>
                    </div>
                </div>

                <div v-if="activeTab === 'students'" class="space-y-3">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mb-2">
                        <div class="flex flex-wrap gap-1.5">
                            <button @click="studentSubTab = 'accepted'" :class="[ studentSubTab === 'accepted' ? 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800/50' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50' ]" class="px-3 py-1 text-[10px] font-bold rounded-full transition-all border shadow-sm">
                                Accepted ({{ processedStudents.length }})
                            </button>
                            <button @click="studentSubTab = 'pending'" :class="[ studentSubTab === 'pending' ? 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/40 dark:text-yellow-300 dark:border-yellow-800/50' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50' ]" class="px-3 py-1 text-[10px] font-bold rounded-full transition-all border shadow-sm flex items-center gap-1">
                                Pending <span v-if="pendingStudents.length > 0" class="bg-red-500 text-white text-[8px] px-1 rounded-full animate-pulse">{{ pendingStudents.length }}</span>
                            </button>
                            <button 
                                @click="studentSubTab = 'ranking'" 
                                class="px-3 py-1 text-[10px] font-bold rounded-full transition-all border shadow-sm flex items-center gap-1"
                                :class="studentSubTab === 'ranking' ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-white text-slate-600 border-slate-200'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 18a2 2 0 002 2h8a2 2 0 002-2M6 18V9m12 9V9M12 11a3 3 0 100 6 3 3 0 000-6z"></path>
                                </svg>
                                Leaderboard
                            </button>
                        </div>

                        <div v-if="studentSubTab !== 'pending'" class="flex items-center gap-2">
                            <label class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Sort By:</label>
                            <select v-model="studentSort" class="text-[10px] font-bold rounded-md bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 py-1 pl-2 pr-6 focus:ring-blue-500 shadow-sm">
                                <option value="alpha_asc">Alphabetical (A - Z)</option>
                                <option value="rank_desc">Rank (1 to Bottom)</option>
                                <option value="rank_asc">Rank (Bottom to 1)</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="studentSubTab === 'accepted'">
                        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                            <div v-if="processedStudents.length > 0">
                                <table class="w-full text-left text-xs text-slate-500 dark:text-slate-400">
                                    <thead class="text-[9px] uppercase font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700">
                                        <tr>
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2 text-center">Current Rank</th>
                                            <th class="px-3 py-2 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                        <tr v-for="enrollment in processedStudents" :key="enrollment.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                            <td class="px-3 py-2 flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-[9px]">{{ enrollment.user.name.charAt(0) }}</div>
                                                <span class="font-bold text-slate-900 dark:text-white">{{ enrollment.user.name }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-center font-bold" :class="enrollment.rank <= 3 ? 'text-yellow-600' : 'text-slate-500'">#{{ enrollment.rank }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <button @click="removeStudent(enrollment.user_id)" class="text-red-500 hover:text-red-700 text-[10px] font-bold bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded border border-red-100 dark:border-red-900/30">Remove</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-6 text-slate-400 text-[10px] italic">
                                No students enrolled yet. Share the code: <span class="font-mono font-bold text-slate-600 dark:text-slate-300">{{ course.enrollment_code }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="studentSubTab === 'ranking'" class="space-y-2">
                        <div v-if="processedStudents.length > 0">
                            <div v-for="student in processedStudents" :key="student.id" class="flex items-center justify-between bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm transition hover:border-orange-300">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs" :class="getRankClass(student.rank)">
                                        #{{ student.rank }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white leading-none mb-1">{{ student.user.name }}</p>
                                        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">{{ student.user.email }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-black text-lg text-slate-800 dark:text-slate-200">{{ student.totalScore }} <span class="text-[9px] font-bold text-slate-400 uppercase">Pts</span></div>
                                    <div class="text-[10px] font-bold" :class="student.percentage > 75 ? 'text-emerald-500' : 'text-slate-500'">{{ student.percentage }}% Avg</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-slate-400 text-[10px] italic">No data to rank yet.</div>
                    </div>

                    <div v-if="studentSubTab === 'pending'">
                        <div v-if="pendingStudents.length > 0" class="space-y-1.5">
                            <div v-for="enrollment in pendingStudents" :key="enrollment.id" class="flex items-center justify-between bg-white dark:bg-slate-800 p-2 rounded border border-slate-200 dark:border-slate-700 shadow-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 flex items-center justify-center font-bold text-[9px]">{{ enrollment.user.name.charAt(0) }}</div>
                                    <div class="leading-tight">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white">{{ enrollment.user.name }}</p>
                                        <p class="text-[9px] text-slate-500">{{ enrollment.user.email }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-1.5">
                                    <button @click="removeStudent(enrollment.user_id)" class="text-[10px] font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-2 py-1 rounded transition">Reject</button>
                                    <button @click="approveStudent(enrollment.user_id)" class="text-[10px] font-bold text-white bg-blue-600 hover:bg-blue-500 px-2 py-1 rounded shadow-sm transition">Accept</button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 text-[10px] italic shadow-sm">
                            There are no pending join requests.
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'announcements'" class="space-y-4 max-w-3xl w-full">
                    <div v-for="post in course.announcements" :key="post.id" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden hover:border-red-300 dark:hover:border-red-500 transition-colors duration-200">
                        
                        <div class="p-3 pb-2 flex justify-between items-start border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 shrink-0 rounded-full bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center text-xs font-bold">{{ post.user.name.charAt(0) }}</div>
                                <div class="leading-tight">
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ post.user.name }}</h4>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ new Date(post.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) }}</p>
                                </div>
                            </div>
                            <button @click="deleteItem(route('teacher.announcements.destroy', post.id))" class="text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 p-1.5 rounded transition" title="Delete post">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                        
                        <div class="p-4 space-y-3">
                            <h3 v-if="post.title" class="text-base font-bold text-slate-900 dark:text-white">{{ post.title }}</h3>
                            
                            <div v-if="post.video_link" class="w-full mb-3">
                                <iframe 
                                    v-if="getYouTubeEmbedUrl(post.video_link)"
                                    :src="getYouTubeEmbedUrl(post.video_link)" 
                                    class="w-full aspect-video rounded-lg shadow-sm border border-slate-200 dark:border-slate-700"
                                    frameborder="0" 
                                    allowfullscreen>
                                </iframe>
                                <a v-else :href="post.video_link" target="_blank" class="text-xs text-blue-600 hover:underline inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/20 px-3 py-2 rounded-lg border border-blue-100 dark:border-blue-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    Watch Attached Video
                                </a>
                            </div>

                            <div class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed announcement-content" v-html="post.content"></div>
                        </div>
                        
                        <div class="px-3 py-2 flex items-center gap-2 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                            <button @click="toggleComments(post)" class="text-[10px] font-bold text-slate-500 hover:text-red-600 flex items-center gap-1 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                Comments <span v-if="post.comments.length" class="ml-0.5 text-[9px] bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400 px-1.5 py-0.5 rounded-full">{{ post.comments.length }}</span>
                            </button>
                        </div>

                        <div v-if="post.showComments" class="bg-slate-50 dark:bg-slate-900/50 p-3 border-t border-slate-100 dark:border-slate-700">
                            <div v-if="post.comments.length > 0" class="space-y-1.5 mb-2">
                                <div v-for="comment in post.comments" :key="comment.id" class="flex gap-1.5 group">
                                    <div class="w-4 h-4 shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[7px] font-bold text-slate-600 dark:text-slate-300">{{ comment.user.name.charAt(0) }}</div>
                                    <div class="bg-white dark:bg-slate-800 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 flex-1 relative shadow-sm leading-tight">
                                        <div class="flex justify-between items-start">
                                            <span class="font-bold text-slate-900 dark:text-white text-[9px]">{{ comment.user.name }}</span>
                                            <button @click="deleteItem(route('comments.destroy', comment.id))" class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity p-0.5">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                        <span class="text-[9px] text-slate-700 dark:text-slate-300 block mt-0.5">{{ comment.content }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1.5 items-center">
                                <div class="w-4 h-4 shrink-0 rounded-full bg-red-600 flex items-center justify-center text-white text-[7px] font-bold">{{ currentUser.name.charAt(0) }}</div>
                                <div class="flex-1 relative">
                                    <input v-model="formComment.content" @keyup.enter="submitComment(post.id)" type="text" placeholder="Write a reply..." class="w-full bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 rounded py-1 px-2 text-[9px] focus:ring-1 focus:ring-red-500 h-6 pr-6 shadow-sm" />
                                    <button @click="submitComment(post.id)" class="absolute right-0.5 top-0.5 text-red-600 hover:text-red-500 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 p-0.5 rounded transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="course.announcements.length === 0" class="text-center py-6 bg-slate-50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                        <p class="text-slate-400 dark:text-slate-500 text-[10px] font-medium">No announcements yet. Click the red icon to post.</p>
                    </div>
                </div>

                <div v-if="activeTab === 'assignments'" class="space-y-3 max-w-3xl">
                    
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg gap-1 mb-2">
                        <button @click="assignmentFilter = 'upcoming'" class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all" :class="assignmentFilter === 'upcoming' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Upcoming
                        </button>
                        <button @click="assignmentFilter = 'past_due'" class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all" :class="assignmentFilter === 'past_due' ? 'bg-white dark:bg-slate-700 text-red-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Past Due
                        </button>
                        <button @click="assignmentFilter = 'completed'" class="flex-1 py-1.5 text-[10px] font-bold rounded-md flex items-center justify-center gap-1 transition-all" :class="assignmentFilter === 'completed' ? 'bg-white dark:bg-slate-700 text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Completed
                        </button>
                    </div>

                    <div v-if="filteredAssignments.length > 0" class="space-y-2">
                        <Link v-for="assignment in filteredAssignments" :key="assignment.id" 
                              :href="route('teacher.assignments.show', { assignment: assignment.id, source: 'course' })"
                              class="group bg-white dark:bg-slate-800 rounded-lg p-3 border border-slate-200 dark:border-slate-700 hover:border-blue-600 dark:hover:border-blue-500 shadow-sm transition-all duration-200 flex items-center gap-3">
                            <div class="shrink-0 p-2 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-[13px] font-bold text-slate-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">{{ assignment.title }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded">Due: {{ assignment.due_date ? new Date(assignment.due_date).toLocaleDateString() : 'No Date' }}</span>
                                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">{{ assignment.points }} pts</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-8 bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                        <p class="text-slate-400 dark:text-slate-500 text-[10px] font-medium">No {{ assignmentFilter.replace('_', ' ') }} assignments.</p>
                    </div>
                </div>

                <div v-if="activeTab === 'materials'" class="space-y-4 max-w-3xl">
                    
                    <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl gap-1 mb-2">
                        <button @click="materialFilter = 'active'" 
                            class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all"
                            :class="materialFilter === 'active' ? 'bg-white dark:bg-slate-700 text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Active Materials
                        </button>
                        <button @click="materialFilter = 'archived'" 
                            class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all flex items-center justify-center gap-1.5"
                            :class="materialFilter === 'archived' ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                            Archive <span v-if="archivedMaterials.length" class="bg-slate-200 text-slate-600 dark:bg-slate-900/40 dark:text-slate-400 px-1.5 py-0.5 rounded-full text-[8px]">{{ archivedMaterials.length }}</span>
                        </button>
                    </div>

                    <div v-if="displayedMaterials.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                        <div v-for="lesson in displayedMaterials" :key="lesson.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition group gap-3">
                            <div class="flex items-center gap-3 min-w-0 w-full">
                                <div class="shrink-0 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-yellow-600 dark:text-yellow-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                                <div class="flex flex-col min-w-0 w-full">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ lesson.title }}</span>
                                        
                                        <span v-if="materialFilter === 'archived'" class="text-[8px] bg-slate-100 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded uppercase font-black tracking-widest shrink-0">Archived</span>
                                        <span v-else-if="lesson.approval_status === 'pending' && requireApproval" class="text-[8px] bg-orange-100 text-orange-700 border border-orange-200 px-1.5 py-0.5 rounded uppercase font-black tracking-widest shrink-0">Pending Admin</span>
                                        <span v-else-if="lesson.approval_status === 'rejected'" class="text-[8px] bg-red-100 text-red-700 border border-red-200 px-1.5 py-0.5 rounded uppercase font-black tracking-widest shrink-0">Rejected</span>
                                        <span v-else-if="lesson.approval_status === 'approved'" class="text-[8px] bg-emerald-100 text-emerald-700 border border-emerald-200 px-1.5 py-0.5 rounded uppercase font-black tracking-widest shrink-0">Approved</span>
                                    </div>

                                    <div class="flex items-center gap-2 text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                                        <span v-if="lesson.available_from">From: {{ new Date(lesson.available_from).toLocaleDateString() }}</span>
                                        <span v-if="lesson.available_until && materialFilter === 'active'" class="text-red-400">Closes: {{ new Date(lesson.available_until).toLocaleDateString() }}</span>
                                        <span v-if="materialFilter === 'archived'" class="text-slate-500">Archived On: {{ new Date(lesson.available_until).toLocaleDateString() }}</span>
                                        <span v-if="!lesson.available_from && !lesson.available_until && materialFilter === 'active'">Always Available</span>
                                    </div>

                                    <div v-if="lesson.approval_status === 'rejected'" class="mt-2 p-2.5 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 rounded-lg max-w-lg">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            <span class="text-[9px] font-black uppercase text-red-600 tracking-widest">Admin Feedback</span>
                                        </div>
                                        <p class="text-[10px] text-red-700 dark:text-red-300 italic">{{ lesson.rejection_note || 'No specific reason provided. Please review guidelines.' }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                <a :href="`/storage/${lesson.attachment_path}`" target="_blank" class="text-[9px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-3 py-1.5 rounded hover:bg-slate-200 dark:hover:bg-slate-600 transition shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> View
                                </a>
                                <a :href="`/storage/${lesson.attachment_path}`" download class="text-[9px] font-black uppercase tracking-widest bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50 transition shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Download
                                </a>                             

                                <button v-if="lesson.approval_status === 'rejected'" @click="openResubmitModal(lesson)" class="text-[9px] font-black uppercase tracking-widest bg-blue-600 text-white border border-blue-600 px-3 py-1.5 rounded hover:bg-blue-500 transition shadow-sm">Resubmit</button>

                                <button v-if="materialFilter === 'archived'" @click="requestUnarchive(lesson.id)" class="text-[9px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1.5 rounded hover:bg-blue-100 transition shadow-sm">Req Unarchive</button>
                                
                                <button @click="deleteItem(route('teacher.lessons.destroy', lesson.id))" class="text-slate-300 hover:text-red-500 dark:text-slate-600 dark:hover:text-red-400 transition p-1.5 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-10 bg-slate-50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                        <p class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest">No {{ materialFilter }} materials found.</p>
                    </div>
                </div>

            </div>
        </div>
        
        <Modal :show="showLessonModal" @close="showLessonModal = false" maxWidth="sm">
            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Upload Material
                </h3>
                <form @submit.prevent="submitLesson" class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Material Title *</label>
                        <input v-model="formLesson.title" type="text" placeholder="e.g., Chapter 1 Presentation" class="w-full flex h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-slate-400 dark:border-slate-800" required>
                        <InputError class="mt-1 text-[9px]" :message="formLesson.errors.title" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Show From *</label>
                            <input v-model="formLesson.available_from" type="datetime-local" class="w-full flex h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-[10px] font-medium shadow-sm dark:border-slate-800 text-slate-700 dark:text-slate-300" required>
                            <InputError class="mt-1 text-[9px]" :message="formLesson.errors.available_from" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Archive On *</label>
                            <input v-model="formLesson.available_until" type="datetime-local" class="w-full flex h-9 rounded-md border border-slate-200 bg-transparent px-3 py-1 text-[10px] font-medium shadow-sm dark:border-slate-800 text-slate-700 dark:text-slate-300" required>
                            <InputError class="mt-1 text-[9px]" :message="formLesson.errors.available_until" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Select File (Max: 15MB) *</label>
                        <input type="file" @input="formLesson.file = $event.target.files[0]" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 cursor-pointer border border-slate-200 dark:border-slate-800 rounded-md p-1" required>
                        <InputError class="mt-1 text-[9px]" :message="formLesson.errors.file" />
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showLessonModal = false" class="text-[10px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 uppercase tracking-widest">Cancel</button>
                        <button class="bg-yellow-500 hover:bg-yellow-400 text-white px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition-colors" :disabled="formLesson.processing">Upload</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showResubmitModal" @close="showResubmitModal = false" maxWidth="sm">
            <div class="p-5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-lg">
                <h3 class="font-black text-sm text-slate-900 dark:text-white uppercase tracking-tight mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Resubmit Material
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">Please upload a corrected version of <span class="font-bold text-slate-800 dark:text-slate-200">"{{ lessonToResubmit?.title }}"</span> based on the admin's feedback.</p>
                
                <form @submit.prevent="submitResubmit" class="space-y-4">
                    <div>
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Select New File (Max: 15MB) *</label>
                        <input type="file" @input="formResubmit.file = $event.target.files[0]" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png,.zip" class="w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 dark:border-slate-800 rounded-md p-1" required>
                        <InputError class="mt-1 text-[9px]" :message="formResubmit.errors.file" />
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showResubmitModal = false" class="text-[10px] text-slate-500 px-3 py-1.5 font-bold hover:text-slate-700 uppercase tracking-widest">Cancel</button>
                        <button :disabled="formResubmit.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest shadow-sm transition-colors">Submit</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showAnnouncementModal" @close="showAnnouncementModal = false" maxWidth="2xl">
            <div class="p-5 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-base text-slate-900 dark:text-white mb-4">Post Announcement</h3>
                <form @submit.prevent="submitAnnouncement" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Post Title (Optional)</label>
                        <input v-model="formAnnouncement.title" type="text" placeholder="e.g., Lesson 4 Updates" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:ring-red-500 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">External Video Link (Optional)</label>
                        <input v-model="formAnnouncement.video_link" type="url" placeholder="Paste YouTube or Vimeo URL here..." class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:ring-red-500 py-2" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Description</label>
                        <RichTextEditor v-model="formAnnouncement.content" placeholder="Write your announcement details here..." />
                    </div>
                    <div class="flex justify-end gap-2 mt-4 pt-2 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showAnnouncementModal = false" class="text-xs text-slate-500 px-3 py-2 font-bold hover:text-slate-700">Cancel</button>
                        <button :disabled="formAnnouncement.processing" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded text-xs font-bold shadow-sm">Post Update</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showAssignmentModal" @close="showAssignmentModal = false" maxWidth="md">
            <div class="p-4 bg-white dark:bg-slate-800 rounded-lg">
                <h3 class="font-bold text-sm text-slate-900 dark:text-white mb-3">Create Assignment</h3>
                <form @submit.prevent="submitAssignment" class="space-y-2.5">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Title</label>
                            <input v-model="formAssignment.title" type="text" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-1.5 text-[10px] focus:ring-blue-500" required autofocus />
                            <InputError class="mt-1 text-[9px]" :message="formAssignment.errors.title" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Type</label>
                            <select v-model="formAssignment.type" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-1.5 text-[10px] focus:ring-blue-500" required>
                                <option value="assignment">Assignment</option>
                                <option value="quiz">Quiz</option>
                                <option value="exam">Exam</option>
                            </select>
                            <InputError class="mt-1 text-[9px]" :message="formAssignment.errors.type" />
                        </div>
                    </div>
                    <div>
                         <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Instructions</label>
                        <textarea v-model="formAssignment.description" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-1.5 text-[10px] h-16 focus:ring-blue-500 resize-none" placeholder="Instructions..."></textarea>
                        <InputError class="mt-1 text-[9px]" :message="formAssignment.errors.description" />
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Attachments (Optional)</label>
                        <div class="relative">
                            <input type="file" multiple @change="e => formAssignment.files = Array.from(e.target.files)" class="block w-full text-[9px] text-slate-500 file:mr-2 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:text-[9px] file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 cursor-pointer bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded" />
                        </div>
                        <div v-if="formAssignment.files && formAssignment.files.length > 0" class="mt-1 space-y-0.5">
                            <div v-for="(file, index) in formAssignment.files" :key="index" class="text-[9px] text-slate-600 dark:text-slate-400 flex items-center gap-1">
                                <svg class="w-2.5 h-2.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                {{ file.name }}
                            </div>
                        </div>
                        <InputError class="mt-1 text-[9px]" :message="formAssignment.errors.files" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Points</label>
                            <input v-model="formAssignment.points" type="number" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-1.5 text-[10px] focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-[9px] font-bold uppercase text-slate-500 mb-0.5">Due Date</label>
                            <input v-model="formAssignment.due_date" type="datetime-local" class="w-full rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white p-1.5 text-[10px] focus:ring-blue-500" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" @click="showAssignmentModal = false" class="text-[10px] text-slate-500 px-2 py-1 font-bold hover:text-slate-700">Cancel</button>
                        <button :disabled="formAssignment.processing" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1.5 rounded text-[10px] font-bold shadow-sm">Create</button>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.announcement-content :deep(iframe) {
    width: 100% !important;
    height: auto;
    aspect-ratio: 16 / 9;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}
.announcement-content :deep(a) {
    color: #2563eb;
    text-decoration: underline;
    cursor: pointer;
}
.announcement-content :deep(ul) {
    list-style-type: disc;
    padding-left: 1rem;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
}
.announcement-content :deep(ol) {
    list-style-type: decimal;
    padding-left: 1rem;
    margin-top: 0.25rem;
    margin-bottom: 0.25rem;
}
</style>