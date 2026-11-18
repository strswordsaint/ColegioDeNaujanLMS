<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Download, Search, FileSpreadsheet, Users, BookOpen } from 'lucide-vue-next';
import * as XLSX from 'xlsx';

const props = defineProps({
    courses: Array
});

const searchQuery = ref('');
const selectedCourseId = ref('all');
const selectedTeacher = ref('all');

// Bidirectional dependent filter lists
const availableTeachers = computed(() => {
    const sourceCourses = selectedCourseId.value !== 'all'
        ? props.courses.filter(c => c.id === selectedCourseId.value)
        : props.courses;

    const teachers = new Set(sourceCourses.map(c => c.teacher).filter(Boolean));
    return Array.from(teachers).sort();
});

const availableCourses = computed(() => {
    if (selectedTeacher.value !== 'all') {
        return props.courses.filter(c => c.teacher === selectedTeacher.value);
    }
    return props.courses;
});

// Keep both dropdowns in sync if a conflicting selection exists
watch(selectedTeacher, (newTeacher) => {
    if (newTeacher !== 'all' && selectedCourseId.value !== 'all') {
        const stillValid = props.courses.some(
            c => c.id === selectedCourseId.value && c.teacher === newTeacher
        );
        if (!stillValid) {
            selectedCourseId.value = 'all';
        }
    }
});

watch(selectedCourseId, (newCourseId) => {
    if (newCourseId !== 'all' && selectedTeacher.value !== 'all') {
        const course = props.courses.find(c => c.id === newCourseId);
        if (course && course.teacher !== selectedTeacher.value) {
            selectedTeacher.value = 'all';
        }
    }
});

const filteredCourses = computed(() => {
    let result = props.courses;

    if (selectedCourseId.value !== 'all') {
        result = result.filter(c => c.id === selectedCourseId.value);
    }

    if (selectedTeacher.value !== 'all') {
        result = result.filter(c => c.teacher === selectedTeacher.value);
    }

    if (searchQuery.value) {
        const lowerSearch = searchQuery.value.toLowerCase().trim();
        
        result = result.map(c => {
            const courseMatches = c.title.toLowerCase().includes(lowerSearch) || 
                                  c.teacher.toLowerCase().includes(lowerSearch) ||
                                  (c.enrollment_code && c.enrollment_code.toLowerCase().includes(lowerSearch));
            
            const matchingStudents = c.students ? c.students.filter(s => 
                s.name.toLowerCase().includes(lowerSearch) || 
                (s.email && s.email.toLowerCase().includes(lowerSearch))
            ) : [];
            
            if (courseMatches) {
                return c;
            } else if (matchingStudents.length > 0) {
                return { ...c, students: matchingStudents };
            }
            
            return null;
        }).filter(Boolean);
    }

    return result;
});

const formatYearLevel = (level) => {
    const levels = { 'beginner': '1st Year', 'intermediate': '2nd Year', 'advanced': '3rd Year', 'final': '4th Year' };
    return levels[level] || level;
};

const getStudentTaskScore = (student, assignmentId) => {
    if (!student || !student.submissions) return null;
    const sub = student.submissions.find(s => s.assignment_id === assignmentId);
    return sub && sub.grade !== null ? sub.grade : null;
};

// CLEAN EXPORT: Single top metadata row (Course Code | Teacher), then Student Name + Task Title (Points)
const downloadExcel = () => {
    const wb = XLSX.utils.book_new();

    filteredCourses.value.forEach(course => {
        const assignments = course.assignments || [];
        const taskHeaders = assignments.map(a => `${a.title} (${Number(a.points) || 0})`);

        const wsData = [
            [`Course Code: ${course.enrollment_code || 'N/A'}`, `Teacher: ${course.teacher || 'Unassigned'}`],
            [],
            ['Student Name', ...taskHeaders]
        ];

        if (course.students && course.students.length > 0) {
            const sortedStudents = [...course.students].sort((a, b) => a.name.localeCompare(b.name));
            sortedStudents.forEach(student => {
                const taskScores = assignments.map(a => {
                    const score = getStudentTaskScore(student, a.id);
                    return score !== null && !isNaN(score) ? Number(score) : 0;
                });

                wsData.push([
                    student.name,
                    ...taskScores
                ]);
            });
        } else {
            wsData.push(['No students enrolled.']);
        }

        const ws = XLSX.utils.aoa_to_sheet(wsData);

        ws['!cols'] = [
            { wch: 32 }, // Student Name
            ...assignments.map(() => ({ wch: 20 }))
        ];

        let safeSheetName = course.title.replace(/[\\\/\?\*\[\]]/g, '').substring(0, 31);
        XLSX.utils.book_append_sheet(wb, ws, safeSheetName);
    });

    let fileName = "System_Grades_Report.xlsx";
    if (selectedCourseId.value !== 'all' && filteredCourses.value.length > 0) {
        fileName = `${filteredCourses.value[0].title.replace(/\s+/g, '_')}_Grades.xlsx`;
    }

    XLSX.writeFile(wb, fileName);
};
</script>

<template>
    <Head title="Grades Overview" />

    <AuthenticatedLayout>
        
        <!-- MOBILE FLOATING FAB -->
        <div class="md:hidden fixed bottom-[150px] right-4 z-[999] flex flex-col gap-2 items-center pointer-events-none">
            <button @click="downloadExcel" class="group relative pointer-events-auto flex items-center justify-center w-10 h-10 bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 rounded-full border border-black dark:border-white shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-transform active:scale-95">
                <Download class="w-4 h-4" />
                <span class="absolute right-full mr-2 bg-slate-800 text-white text-[9px] font-bold px-2 py-1 rounded opacity-0 group-hover:opacity-100 group-active:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50">Export Excel</span>
            </button>
        </div>

        <div class="max-w-screen-2xl mx-auto flex flex-col h-full">
            
            <div class="mb-4 pb-3 border-b border-slate-200 dark:border-slate-700 shrink-0 px-1 sm:px-0 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <FileSpreadsheet class="w-6 h-6 text-emerald-600 dark:text-emerald-500" />
                        Grades Overview
                    </h1>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1 ml-8">System-wide academic performance</p>
                </div>

                <div class="hidden sm:flex items-center gap-3">
                    <button @click="downloadExcel" class="w-full sm:w-auto justify-center bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 sm:py-2 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm transition-all flex items-center gap-1.5 h-full">
                        <Download class="w-4 h-4 shrink-0" />
                        Export to Excel
                    </button>
                </div>
            </div>

            <!-- RESPONSIVE SEARCH & FILTER CARD -->
            <div class="bg-white dark:bg-slate-800 p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm mb-4 flex flex-row sm:flex-col lg:flex-row gap-1.5 sm:gap-2.5 items-center sm:items-stretch lg:items-center min-w-0 mx-1 sm:mx-0">
                
                <!-- Search -->
                <div class="relative flex-1 min-w-[120px] sm:min-w-[200px]">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <Search class="h-3.5 w-3.5 text-slate-400" />
                    </div>
                    <input v-model="searchQuery" type="text" placeholder="Search students, courses, or instructors..." class="w-full h-8 pl-8 rounded sm:rounded-md bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 sm:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 sm:focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-xs shadow-sm transition-colors" />
                </div>

                <!-- MOBILE FILTERS -->
                <div class="flex sm:hidden flex-row gap-1.5 shrink-0">
                    <div class="relative shrink-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded flex items-center justify-center w-8 h-8 shadow-sm transition group hover:bg-slate-50 dark:hover:bg-slate-700">
                        <Users class="w-4 h-4 text-slate-500 dark:text-slate-400 pointer-events-none" />
                        <select v-model="selectedTeacher" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer dark:[color-scheme:dark]" title="Filter by Instructor">
                            <option value="all" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">All Instructors</option>
                            <option v-for="teacher in availableTeachers" :key="teacher" :value="teacher" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ teacher }}</option>
                        </select>
                        <span class="absolute bottom-full right-0 mb-1 bg-slate-800 text-white text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 group-active:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50 font-bold uppercase tracking-widest">Instructor</span>
                    </div>
                    
                    <div class="relative shrink-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded flex items-center justify-center w-8 h-8 shadow-sm transition group hover:bg-slate-50 dark:hover:bg-slate-700">
                        <BookOpen class="w-4 h-4 text-slate-500 dark:text-slate-400 pointer-events-none" />
                        <select v-model="selectedCourseId" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer dark:[color-scheme:dark]" title="Filter by Course">
                            <option value="all" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">All Courses</option>
                            <option v-for="c in availableCourses" :key="c.id" :value="c.id" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ c.title }}</option>
                        </select>
                        <span class="absolute bottom-full right-0 mb-1 bg-slate-800 text-white text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 group-active:opacity-100 pointer-events-none whitespace-nowrap transition-opacity z-50 font-bold uppercase tracking-widest">Course</span>
                    </div>
                </div>

                <!-- DESKTOP FILTERS -->
                <div class="hidden sm:grid grid-cols-2 lg:flex lg:flex-row gap-2 w-full lg:w-auto shrink-0 mt-1 lg:mt-0">
                    <div class="shrink-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 shadow-sm flex items-center gap-1.5 min-w-[140px]">
                        <Users class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <select v-model="selectedTeacher" class="bg-transparent border-none text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 w-full focus:ring-0 cursor-pointer p-0 m-0 truncate dark:[color-scheme:dark]">
                            <option value="all" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">All Instructors</option>
                            <option v-for="teacher in availableTeachers" :key="teacher" :value="teacher" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ teacher }}</option>
                        </select>
                    </div>
                    
                    <div class="shrink-0 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 shadow-sm flex items-center gap-1.5 min-w-[160px]">
                        <BookOpen class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <select v-model="selectedCourseId" class="bg-transparent border-none text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-300 w-full focus:ring-0 cursor-pointer p-0 m-0 truncate dark:[color-scheme:dark]">
                            <option value="all" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">All Courses</option>
                            <option v-for="c in availableCourses" :key="c.id" :value="c.id" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white">{{ c.title }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-4 pb-8 custom-scrollbar pr-1">
                
                <div v-if="filteredCourses.length === 0" class="text-center py-12 bg-white dark:bg-slate-800 rounded-xl border border-dashed border-slate-200 dark:border-slate-700">
                    <FileSpreadsheet class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">No records match your search.</p>
                </div>

                <div v-for="course in filteredCourses" :key="course.id" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 p-2.5 px-3 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <h2 class="font-black text-[11px] sm:text-xs text-slate-900 dark:text-white uppercase tracking-tight truncate">{{ course.title }}</h2>
                            <span class="text-[7px] sm:text-[8px] font-mono bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 px-1.5 py-0.5 rounded shrink-0">{{ course.enrollment_code }}</span>
                            <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400 hidden sm:inline shrink-0">| {{ course.teacher }} | {{ formatYearLevel(course.difficulty_level) }}</span>
                        </div>
                        <div class="text-[8px] font-black bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 px-1.5 py-0.5 rounded text-slate-600 dark:text-slate-300 shadow-sm shrink-0">
                            {{ course.students.length }} Enrolled
                        </div>
                    </div>

                    <div v-if="course.students.length > 0">
                        <!-- DESKTOP TABLE -->
                        <div class="hidden md:block overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left text-[9px] sm:text-[10px] text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                <thead class="uppercase font-black text-slate-400 bg-slate-50/30 dark:bg-slate-900/20 border-b border-slate-100 dark:border-slate-700/50">
                                    <tr>
                                        <th class="px-2 sm:px-3 py-1.5 text-center w-6">#</th>
                                        <th class="px-2 sm:px-3 py-1.5 sticky left-0 bg-slate-50 dark:bg-slate-900 z-10">Student</th>
                                        <th v-for="a in course.assignments" :key="a.id" class="px-2 sm:px-3 py-1.5 text-center border-l border-slate-100 dark:border-slate-700/50">
                                            <span class="text-slate-700 dark:text-slate-300">{{ a.title }} ({{ a.points }})</span>
                                        </th>
                                        <th class="px-2 sm:px-3 py-1.5 text-right border-l border-emerald-200 dark:border-emerald-800 bg-emerald-50/30 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400">Overall</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
                                    <tr v-for="(student, index) in course.students" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                        <td class="px-2 sm:px-3 py-1 text-center font-bold text-slate-400">{{ index + 1 }}</td>
                                        <td class="px-2 sm:px-3 py-1 sticky left-0 bg-white dark:bg-slate-800 z-10">
                                            <div class="font-bold text-slate-900 dark:text-white truncate max-w-[180px]">{{ student.name }}</div>
                                        </td>
                                        <td v-for="a in course.assignments" :key="a.id" class="px-2 sm:px-3 py-1 text-center font-medium border-l border-slate-50 dark:border-slate-700/30">
                                            <span v-if="getStudentTaskScore(student, a.id) !== null" class="font-bold text-slate-700 dark:text-slate-200">
                                                {{ getStudentTaskScore(student, a.id) }}
                                            </span>
                                            <span v-else class="text-[7px] font-black text-red-500 dark:text-red-400 uppercase tracking-widest">
                                                Missing
                                            </span>
                                        </td>
                                        <td class="px-2 sm:px-3 py-1 text-right border-l border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/10 dark:bg-emerald-900/5">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400': student.percentage >= 85,
                                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400': student.percentage >= 75 && student.percentage < 85,
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': student.percentage < 75
                                                }">
                                                {{ student.percentage }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- MOBILE VIEW -->
                        <div class="md:hidden flex flex-col divide-y divide-slate-50 dark:divide-slate-700/30">
                            <div v-for="(student, index) in course.students" :key="student.id" class="p-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition flex flex-col gap-1.5">
                                <div class="flex justify-between items-center gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[9px] font-black text-slate-400 w-4 text-center shrink-0">{{ index + 1 }}</span>
                                        <span class="text-[11px] font-bold text-slate-900 dark:text-white truncate leading-tight">{{ student.name }}</span>
                                    </div>
                                    <span class="text-[9px] font-black px-1.5 py-0.5 rounded shrink-0" 
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400': student.percentage >= 85,
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400': student.percentage >= 75 && student.percentage < 85,
                                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': student.percentage < 75
                                        }">
                                        {{ student.percentage }}%
                                    </span>
                                </div>
                                <div v-if="course.assignments && course.assignments.length > 0" class="flex flex-wrap gap-1.5 ml-6">
                                    <span v-for="a in course.assignments" :key="a.id" class="text-[8px] font-bold bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                        {{ a.title }} ({{ a.points }}):
                                        <span v-if="getStudentTaskScore(student, a.id) !== null" class="text-slate-900 dark:text-white font-black">{{ getStudentTaskScore(student, a.id) }}</span>
                                        <span v-else class="text-red-500 dark:text-red-400 font-black uppercase tracking-wider">Missing</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="p-4 text-center text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                        No students match search.
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 10px; }
</style>