<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    course: Object,
    courses: Array,
    assignments: Array,
    students: Array
});

const selectedCourseId = ref(props.course?.id || '');
const filterStatus = ref('all'); // all, complete, missing
const sortBy = ref('name-asc'); // name-asc, name-desc, grade-desc, grade-asc

// Change Course dropdown logic
const switchCourse = () => {
    if (selectedCourseId.value) {
        router.get(route('teacher.gradebook.index', selectedCourseId.value));
    }
};

const totalCoursePoints = computed(() => {
    return props.assignments?.reduce((sum, current) => sum + current.points, 0) || 0;
});

const getSubmission = (student, assignmentId) => {
    return student.submissions.find(s => s.assignment_id === assignmentId);
};

const calculateStudentPercentage = (student) => {
    if (totalCoursePoints.value === 0) return 0;
    let earned = 0;
    student.submissions.forEach(sub => {
        if (sub.grade !== null) earned += sub.grade;
    });
    return parseFloat(((earned / totalCoursePoints.value) * 100).toFixed(1));
};

// --- FILTERING AND SORTING LOGIC ---
const processedStudents = computed(() => {
    if (!props.students) return [];
    
    let result = [...props.students];

    // Apply Filter
    if (filterStatus.value === 'missing') {
        result = result.filter(student => {
            return props.assignments.some(assignment => {
                const sub = getSubmission(student, assignment.id);
                return !sub || sub.grade === null;
            });
        });
    } else if (filterStatus.value === 'complete') {
        result = result.filter(student => {
            return props.assignments.every(assignment => {
                const sub = getSubmission(student, assignment.id);
                return sub && sub.grade !== null;
            });
        });
    }

    // Apply Sort
    result.sort((a, b) => {
        if (sortBy.value === 'name-asc') return a.name.localeCompare(b.name);
        if (sortBy.value === 'name-desc') return b.name.localeCompare(a.name);
        
        const gradeA = calculateStudentPercentage(a);
        const gradeB = calculateStudentPercentage(b);
        if (sortBy.value === 'grade-desc') return gradeB - gradeA;
        if (sortBy.value === 'grade-asc') return gradeA - gradeB;
    });

    return result;
});

// --- EXPORT TO EXCEL/CSV LOGIC ---
const exportToCSV = () => {
    if (processedStudents.value.length === 0) return alert("No data to export!");

    // Build the Header Row
    let csvContent = "Student Name,";
    props.assignments.forEach(a => csvContent += `"${a.title} (/${a.points})",`);
    csvContent += "Total Percentage\n";

    // Build the Data Rows
    processedStudents.value.forEach(student => {
        let row = `"${student.name}",`;
        props.assignments.forEach(assignment => {
            const sub = getSubmission(student, assignment.id);
            if (sub && sub.grade !== null) row += `${sub.grade},`;
            else row += `Missing,`;
        });
        row += `${calculateStudentPercentage(student)}%\n`;
        csvContent += row;
    });

    // Create a downloadable Blob
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `Gradebook_${props.course.title.replace(/\s+/g, '_')}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<template>
    <Head :title="`Gradebook - ${course?.title || 'No Courses'}`" />

    <AuthenticatedLayout>
        <div v-if="!course" class="max-w-3xl mx-auto mt-10 text-center py-16 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm">
            <h2 class="text-xl font-black text-slate-800 dark:text-white mb-2">No Courses Found</h2>
            <p class="text-slate-500 text-sm mb-6">You need to create a course and approve students before using the Gradebook.</p>
            <Link :href="route('teacher.courses.index')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-lg font-bold text-sm shadow-sm transition">Go to Courses</Link>
        </div>

        <div v-else class="flex flex-col h-[calc(100vh-6rem)] md:h-[calc(100vh-4rem)] max-w-7xl mx-auto">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 shrink-0">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight leading-none flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Gradebook
                    </h1>
                </div>

                <button @click="exportToCSV" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest shadow-sm transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"></path></svg>
                    Export CSV
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm mb-4 shrink-0">
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1 mb-0.5">Course</label>
                    <select v-model="selectedCourseId" @change="switchCourse" class="text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800 border-none rounded shadow-inner py-1.5 focus:ring-1 focus:ring-indigo-500 cursor-pointer w-full">
                        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.title }}</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1 mb-0.5">Filter</label>
                    <select v-model="filterStatus" class="text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800 border-none rounded shadow-inner py-1.5 focus:ring-1 focus:ring-indigo-500 cursor-pointer w-full">
                        <option value="all">All Students ({{ students.length }})</option>
                        <option value="complete">Fully Graded</option>
                        <option value="missing">Missing Grades</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1 mb-0.5">Sort</label>
                    <select v-model="sortBy" class="text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800 border-none rounded shadow-inner py-1.5 focus:ring-1 focus:ring-indigo-500 cursor-pointer w-full">
                        <option value="name-asc">Name (A - Z)</option>
                        <option value="name-desc">Name (Z - A)</option>
                        <option value="grade-desc">Highest Grade</option>
                        <option value="grade-asc">Lowest Grade</option>
                    </select>
                </div>
            </div>

            <div class="flex-1 min-h-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm flex flex-col overflow-hidden relative">
                <div class="overflow-auto flex-1">
                    <table class="w-full text-left text-xs whitespace-nowrap border-collapse">
                        <thead class="sticky top-0 z-20 shadow-sm">
                            <tr>
                                <th class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border-b border-r border-slate-200 dark:border-slate-700 font-black text-[10px] text-slate-600 dark:text-slate-300 uppercase tracking-widest sticky left-0 z-30 shadow-[1px_0_0_0_#e2e8f0] dark:shadow-[1px_0_0_0_#334155]">
                                    Student
                                </th>
                                
                                <th v-for="assignment in assignments" :key="assignment.id" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border-b border-r border-slate-200 dark:border-slate-700 font-black text-[10px] text-slate-600 dark:text-slate-300 uppercase tracking-widest text-center">
                                    <div class="max-w-[100px] truncate mx-auto" :title="assignment.title">{{ assignment.title }}</div>
                                    <div class="text-[9px] text-indigo-600 dark:text-indigo-400 mt-0.5">{{ assignment.points }} pts</div>
                                </th>
                                
                                <th class="px-3 py-2 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 font-black text-[10px] text-slate-600 dark:text-slate-300 uppercase tracking-widest text-right">
                                    Total
                                    <div class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5">{{ totalCoursePoints }} pts</div>
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <tr v-if="processedStudents.length === 0">
                                <td :colspan="assignments.length + 2" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 text-[10px] font-bold uppercase tracking-widest">
                                    No students match your current filters.
                                </td>
                            </tr>
                            
                            <tr v-for="student in processedStudents" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                
                                <td class="px-3 py-2 font-bold text-slate-900 dark:text-white sticky left-0 z-10 bg-white dark:bg-slate-900 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/80 border-r border-slate-100 dark:border-slate-800 shadow-[1px_0_0_0_#f1f5f9] dark:shadow-[1px_0_0_0_#1e293b]">
                                    {{ student.name }}
                                </td>
                                
                                <td v-for="assignment in assignments" :key="assignment.id" class="px-3 py-2 text-center border-r border-slate-100 dark:border-slate-800/50">
                                    <span v-if="getSubmission(student, assignment.id)">
                                        <span v-if="getSubmission(student, assignment.id).grade !== null" class="font-black text-[11px]" :class="getSubmission(student, assignment.id).grade >= (assignment.points * 0.75) ? 'text-emerald-600 dark:text-emerald-400' : (getSubmission(student, assignment.id).grade >= (assignment.points * 0.5) ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')">
                                            {{ getSubmission(student, assignment.id).grade }}
                                        </span>
                                        <span v-else class="text-[8px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest bg-orange-50 dark:bg-orange-900/30 px-1.5 py-0.5 rounded">Needs Grade</span>
                                    </span>
                                    <span v-else class="text-[9px] font-bold text-slate-300 dark:text-slate-600 uppercase tracking-widest">--</span>
                                </td>
                                
                                <td class="px-3 py-2 text-right bg-slate-50/50 dark:bg-slate-900/50 group-hover:bg-slate-100 dark:group-hover:bg-slate-800">
                                    <span class="font-black text-sm" :class="calculateStudentPercentage(student) >= 75 ? 'text-emerald-600 dark:text-emerald-400' : (calculateStudentPercentage(student) >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400')">
                                        {{ calculateStudentPercentage(student) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>