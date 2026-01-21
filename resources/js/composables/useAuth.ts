import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Role, User } from '@/types';

export function useAuth() {
    const page = usePage();

    const user = computed<User>(() => page.props.auth.user as User);

    const roles = computed<Role[]>(() => {
        return user.value.roles?.map(r => r.name) || [];
    });

    const hasRole = (role: Role | Role[]): boolean => {
        if (Array.isArray(role)) {
            return role.some(r => roles.value.includes(r));
        }
        return roles.value.includes(role);
    };

    const isAdmin = computed(() => hasRole('admin'));
    const isDirector = computed(() => hasRole('director'));
    const isCoordinator = computed(() => hasRole('coordinator'));
    const isTeacher = computed(() => hasRole('teacher'));
    const isStudent = computed(() => hasRole('student'));
    const isGuardian = computed(() => hasRole('guardian'));

    const isStaff = computed(() => hasRole(['admin', 'director', 'coordinator']));
    const canManageAcademic = computed(() => hasRole(['admin', 'director', 'coordinator', 'teacher']));

    const primaryRole = computed<Role | null>(() => {
        const priority: Role[] = ['admin', 'director', 'coordinator', 'teacher', 'student', 'guardian'];
        for (const role of priority) {
            if (roles.value.includes(role)) {
                return role;
            }
        }
        return null;
    });

    const roleLabel = computed(() => {
        const labels: Record<Role, string> = {
            admin: 'Administrador',
            director: 'Director',
            coordinator: 'Coordinador',
            teacher: 'Profesor',
            student: 'Estudiante',
            guardian: 'Representante',
        };
        return primaryRole.value ? labels[primaryRole.value] : 'Usuario';
    });

    return {
        user,
        roles,
        hasRole,
        isAdmin,
        isDirector,
        isCoordinator,
        isTeacher,
        isStudent,
        isGuardian,
        isStaff,
        canManageAcademic,
        primaryRole,
        roleLabel,
    };
}
