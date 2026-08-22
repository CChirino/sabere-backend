// Roles del sistema
export type Role = 'admin' | 'director' | 'coordinator' | 'teacher' | 'student' | 'guardian';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles?: { name: Role }[];
    cedula?: string;
    phone?: string;
    birth_date?: string;
    avatar?: string;
    avatar_url?: string;
    bio?: string;
    address?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;
}

// Entidades académicas
export interface EducationLevel {
    id: number;
    name: string;
    code: string;
    description?: string;
    status: boolean;
}

export interface Grade {
    id: number;
    education_level_id: number;
    name: string;
    numeric_equivalent: number;
    status: boolean;
    education_level?: EducationLevel;
}

export interface AcademicPeriod {
    id: number;
    name: string;
    code: string;
    school_year: string;
    start_date: string;
    end_date: string;
    status: boolean;
}

export interface Term {
    id: number;
    academic_period_id: number;
    name: string;
    number: number;
    start_date: string;
    end_date: string;
    weight: number;
    status: boolean;
    academic_period?: AcademicPeriod;
}

export interface Section {
    id: number;
    grade_id: number;
    academic_period_id: number;
    name: string;
    capacity?: number;
    status: boolean;
    grade?: Grade;
    academic_period?: AcademicPeriod;
    enrollments_count?: number;
}

export interface Subject {
    id: number;
    subject_area_id: number;
    name: string;
    code: string;
    description?: string;
    status: boolean;
}

export interface SubjectAssignment {
    id: number;
    teacher_id: number;
    subject_id: number;
    section_id: number;
    academic_period_id: number;
    status: boolean;
    teacher?: User;
    subject?: Subject;
    section?: Section;
    academic_period?: AcademicPeriod;
}

export interface Enrollment {
    id: number;
    student_id: number;
    section_id: number;
    academic_period_id: number;
    enrollment_date: string;
    status: 'active' | 'inactive' | 'transferred' | 'graduated' | 'withdrawn';
    notes?: string;
    student?: User;
    section?: Section;
    academic_period?: AcademicPeriod;
}

export interface Task {
    id: number;
    subject_assignment_id: number;
    term_id: number;
    title: string;
    description?: string;
    instructions?: string;
    type: 'homework' | 'exam' | 'quiz' | 'project' | 'activity';
    max_score: number;
    weight: number;
    due_date?: string;
    available_from?: string;
    is_published: boolean;
    status: boolean;
    subject_assignment?: SubjectAssignment;
    term?: Term;
    submission_status?: string;
    submissions?: TaskSubmission[];
}

export interface TaskSubmission {
    id: number;
    task_id: number;
    student_id: number;
    content?: string;
    file_path?: string;
    submitted_at?: string;
    score?: number;
    feedback?: string;
    graded_by?: number;
    graded_at?: string;
    status: 'pending' | 'submitted' | 'late' | 'graded' | 'returned';
    task?: Task;
    student?: User;
}

export interface StudentScore {
    id: number;
    student_id: number;
    subject_assignment_id: number;
    term_id: number;
    score: number;
    observations?: string;
    graded_by: number;
    graded_at: string;
    is_final: boolean;
    student?: User;
    subject_assignment?: SubjectAssignment;
    term?: Term;
    title?: string;
    max_score?: number;
    type?: string;
}

export interface Schedule {
    id: number;
    subject_assignment_id: number;
    day_of_week: 'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday';
    start_time: string;
    end_time: string;
    classroom?: string;
    notes?: string;
    status: boolean;
    subject_assignment?: SubjectAssignment;
    day_name?: string;
    time_range?: string;
}

export interface SchoolEvent {
    id: number;
    academic_period_id?: number;
    created_by: number;
    title: string;
    description?: string;
    type: 'academic' | 'sports' | 'cultural' | 'administrative';
    start_date: string;
    end_date?: string;
    all_day: boolean;
    location?: string;
    color?: string;
    visibility: 'all' | 'teachers' | 'students' | 'staff';
    send_notification: boolean;
    status: boolean;
    creator?: User;
    academic_period?: AcademicPeriod;
    type_label?: string;
    display_color?: string;
}

// Dashboard Stats
export interface DashboardStats {
    total_users?: number;
    total_students?: number;
    total_teachers?: number;
    total_guardians?: number;
    total_sections?: number;
    total_enrollments?: number;
    active_enrollments?: number;
    total_assignments?: number;
    pending_submissions?: number;
    pending_tasks?: number;
    current_average?: number;
    total_subjects?: number;
}

export interface DashboardData {
    current_period?: AcademicPeriod;
    current_term?: Term;
    stats: DashboardStats;
    assignments?: SubjectAssignment[];
    pending_tasks?: Task[];
    current_scores?: StudentScore[];
    subjects?: SubjectAssignment[];
    upcoming_tasks?: Task[];
    upcoming_events?: SchoolEvent[];
    sections?: Section[];
    students?: { student: User; enrollment?: Enrollment; pending_tasks: number; current_average?: number }[];
    enrollment?: Enrollment;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash?: {
        success?: string;
        error?: string;
    };
};

// Cronogramas
export interface TeacherSyllabus {
    id: number;
    title: string;
    description?: string;
    content_type: 'file' | 'editor' | 'both';
    file_path?: string;
    file_name?: string;
    file_size?: number;
    content?: string;
    objectives?: string[];
    topics?: { week: string; topic: string; description?: string }[];
    evaluation_criteria?: string[];
    resources?: string[];
    is_published: boolean;
    published_at?: string;
    subject_assignment_id: number;
    term_id?: number;
    created_by: number;
    created_at: string;
    updated_at: string;
}

// Circulares
export interface Circular {
    id: number;
    title: string;
    content: string;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    audience: 'all' | 'teachers' | 'students' | 'guardians' | 'staff';
    academic_period_id?: number;
    send_email: boolean;
    send_push: boolean;
    scheduled_at?: string;
    sent_at?: string;
    created_by: number;
    creator?: User;
    created_at: string;
    updated_at: string;
}

// Ayuda
export interface HelpCategory {
    id: number;
    name: string;
    slug: string;
    description?: string;
    icon?: string;
    sort_order: number;
    is_active: boolean;
    articles?: HelpArticle[];
    articles_count?: number;
}

export interface HelpArticle {
    id: number;
    category_id: number;
    title: string;
    slug: string;
    content: string;
    role_target?: string;
    sort_order: number;
    views_count: number;
    is_active: boolean;
    category?: HelpCategory;
    created_at: string;
    updated_at: string;
}

export interface HelpSuggestion {
    id: number;
    user_id: number;
    type: 'article' | 'question';
    subject: string;
    description: string;
    status: 'pending' | 'reviewed' | 'implemented' | 'rejected';
    admin_response?: string;
    reviewed_by?: number;
    reviewed_at?: string;
    user?: User;
    reviewer?: User;
    created_at: string;
}
