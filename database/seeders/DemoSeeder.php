<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\StudentGuardian;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Crear datos de demostración completos para todos los flujos del sistema
     */
    public function run(): void
    {
        $this->command->info('🎓 Creando datos de demostración...');

        // 1. Crear período académico activo
        $period = $this->createAcademicPeriod();
        
        // 2. Crear usuarios adicionales (profesores, estudiantes, representantes)
        $teachers = $this->createTeachers();
        $students = $this->createStudents();
        $guardians = $this->createGuardians($students);
        
        // 3. Crear secciones
        $sections = $this->createSections($period);
        
        // 4. Inscribir estudiantes en secciones
        $this->enrollStudents($students, $sections, $period);
        
        // 5. Crear asignaciones profesor-materia-sección
        $assignments = $this->createSubjectAssignments($teachers, $sections, $period);
        
        // 6. Crear horarios
        $this->createSchedules($assignments);
        
        // 7. Crear tareas
        $tasks = $this->createTasks($assignments, $period);
        
        // 8. Crear entregas de tareas
        $this->createTaskSubmissions($tasks, $sections);
        
        // 9. Crear calificaciones
        $this->createScores($assignments, $sections, $period);
        
        // 10. Crear registros de asistencia
        $this->createAttendance($sections, $assignments, $period);

        $this->command->info('');
        $this->command->info('✅ Datos de demostración creados exitosamente!');
        $this->command->info('');
        $this->showDemoCredentials();
    }

    private function createAcademicPeriod(): AcademicPeriod
    {
        $this->command->info('📅 Creando período académico...');

        $period = AcademicPeriod::firstOrCreate(
            ['code' => '2024-2025'],
            [
                'name' => 'Año Escolar 2024-2025',
                'code' => '2024-2025',
                'school_year' => '2024-2025',
                'start_date' => '2024-09-16',
                'end_date' => '2025-07-15',
                'status' => true,
            ]
        );

        // Crear lapsos si no existen
        if ($period->terms()->count() === 0) {
            Term::create([
                'academic_period_id' => $period->id,
                'name' => 'Primer Lapso',
                'number' => 1,
                'start_date' => '2024-09-16',
                'end_date' => '2024-12-13',
                'weight' => 33.33,
                'status' => true,
            ]);

            Term::create([
                'academic_period_id' => $period->id,
                'name' => 'Segundo Lapso',
                'number' => 2,
                'start_date' => '2025-01-06',
                'end_date' => '2025-03-28',
                'weight' => 33.33,
                'status' => true,
            ]);

            Term::create([
                'academic_period_id' => $period->id,
                'name' => 'Tercer Lapso',
                'number' => 3,
                'start_date' => '2025-04-07',
                'end_date' => '2025-07-15',
                'weight' => 33.34,
                'status' => true,
            ]);
        }

        return $period;
    }

    private function createTeachers(): array
    {
        $this->command->info('👨‍🏫 Creando profesores...');

        $teachersData = [
            ['name' => 'Prof. María López', 'email' => 'profesor@sabere.com'],
            ['name' => 'Prof. Carlos Rodríguez', 'email' => 'carlos.rodriguez@sabere.com'],
            ['name' => 'Prof. Ana Martínez', 'email' => 'ana.martinez@sabere.com'],
            ['name' => 'Prof. José García', 'email' => 'jose.garcia@sabere.com'],
            ['name' => 'Prof. Laura Hernández', 'email' => 'laura.hernandez@sabere.com'],
            ['name' => 'Prof. Miguel Sánchez', 'email' => 'miguel.sanchez@sabere.com'],
        ];

        $teachers = [];
        foreach ($teachersData as $data) {
            $teacher = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$teacher->hasRole('teacher')) {
                $teacher->assignRole('teacher');
            }
            $teachers[] = $teacher;
        }

        return $teachers;
    }

    private function createStudents(): array
    {
        $this->command->info('👨‍🎓 Creando estudiantes...');

        $studentsData = [
            // 4to Grado A
            ['name' => 'Juan Pérez', 'email' => 'estudiante@sabere.com'],
            ['name' => 'María González', 'email' => 'maria.gonzalez@sabere.com'],
            ['name' => 'Carlos Ramírez', 'email' => 'carlos.ramirez@sabere.com'],
            ['name' => 'Ana Fernández', 'email' => 'ana.fernandez@sabere.com'],
            ['name' => 'Luis Torres', 'email' => 'luis.torres@sabere.com'],
            ['name' => 'Sofía Díaz', 'email' => 'sofia.diaz@sabere.com'],
            ['name' => 'Diego Morales', 'email' => 'diego.morales@sabere.com'],
            ['name' => 'Valentina Castro', 'email' => 'valentina.castro@sabere.com'],
            // 4to Grado B
            ['name' => 'Andrés Vargas', 'email' => 'andres.vargas@sabere.com'],
            ['name' => 'Camila Rojas', 'email' => 'camila.rojas@sabere.com'],
            ['name' => 'Sebastián Mendoza', 'email' => 'sebastian.mendoza@sabere.com'],
            ['name' => 'Isabella Herrera', 'email' => 'isabella.herrera@sabere.com'],
            // 5to Grado A
            ['name' => 'Mateo Silva', 'email' => 'mateo.silva@sabere.com'],
            ['name' => 'Luciana Ortiz', 'email' => 'luciana.ortiz@sabere.com'],
            ['name' => 'Nicolás Ruiz', 'email' => 'nicolas.ruiz@sabere.com'],
            ['name' => 'Emma Jiménez', 'email' => 'emma.jimenez@sabere.com'],
            // 3er Grado A
            ['name' => 'Santiago Flores', 'email' => 'santiago.flores@sabere.com'],
            ['name' => 'Martina Acosta', 'email' => 'martina.acosta@sabere.com'],
            ['name' => 'Tomás Medina', 'email' => 'tomas.medina@sabere.com'],
            ['name' => 'Valeria Núñez', 'email' => 'valeria.nunez@sabere.com'],
        ];

        $students = [];
        foreach ($studentsData as $data) {
            $student = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$student->hasRole('student')) {
                $student->assignRole('student');
            }
            $students[] = $student;
        }

        return $students;
    }

    private function createGuardians(array $students): array
    {
        $this->command->info('👨‍👩‍👧 Creando representantes...');

        $guardiansData = [
            ['name' => 'Pedro Pérez', 'email' => 'representante@sabere.com', 'students' => [0, 1]], // Juan y María
            ['name' => 'Carmen González', 'email' => 'carmen.gonzalez@sabere.com', 'students' => [2, 3]], // Carlos y Ana
            ['name' => 'Roberto Torres', 'email' => 'roberto.torres@sabere.com', 'students' => [4, 5]], // Luis y Sofía
            ['name' => 'Lucía Morales', 'email' => 'lucia.morales@sabere.com', 'students' => [6, 7]], // Diego y Valentina
            ['name' => 'Fernando Vargas', 'email' => 'fernando.vargas@sabere.com', 'students' => [8, 9]], // Andrés y Camila
            ['name' => 'Patricia Mendoza', 'email' => 'patricia.mendoza@sabere.com', 'students' => [10, 11]], // Sebastián e Isabella
            ['name' => 'Ricardo Silva', 'email' => 'ricardo.silva@sabere.com', 'students' => [12, 13]], // Mateo y Luciana
            ['name' => 'Gloria Ruiz', 'email' => 'gloria.ruiz@sabere.com', 'students' => [14, 15]], // Nicolás y Emma
            ['name' => 'Héctor Flores', 'email' => 'hector.flores@sabere.com', 'students' => [16, 17]], // Santiago y Martina
            ['name' => 'Marta Medina', 'email' => 'marta.medina@sabere.com', 'students' => [18, 19]], // Tomás y Valeria
        ];

        $guardians = [];
        $relationships = ['father', 'mother', 'guardian'];

        foreach ($guardiansData as $data) {
            $guardian = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            if (!$guardian->hasRole('guardian')) {
                $guardian->assignRole('guardian');
            }

            // Vincular con estudiantes
            foreach ($data['students'] as $index => $studentIndex) {
                if (isset($students[$studentIndex])) {
                    StudentGuardian::firstOrCreate(
                        [
                            'guardian_id' => $guardian->id,
                            'student_id' => $students[$studentIndex]->id,
                        ],
                        [
                            'relationship' => $relationships[array_rand($relationships)],
                            'is_primary' => $index === 0,
                            'can_pickup' => true,
                            'emergency_contact' => true,
                            'phone' => '0414-' . rand(1000000, 9999999),
                            'status' => true,
                        ]
                    );
                }
            }

            $guardians[] = $guardian;
        }

        return $guardians;
    }

    private function createSections(AcademicPeriod $period): array
    {
        $this->command->info('🏫 Creando secciones...');

        $grades = Grade::all();
        $sections = [];

        $sectionNames = ['A', 'B'];

        foreach ($grades as $grade) {
            // Crear 2 secciones por grado (solo para grados 3, 4 y 5)
            if (in_array($grade->numeric_equivalent, [3, 4, 5])) {
                foreach ($sectionNames as $name) {
                    $section = Section::firstOrCreate(
                        [
                            'grade_id' => $grade->id,
                            'academic_period_id' => $period->id,
                            'name' => $name,
                        ],
                        [
                            'capacity' => 30,
                            'status' => true,
                        ]
                    );
                    $sections[] = $section;
                }
            }
        }

        return $sections;
    }

    private function enrollStudents(array $students, array $sections, AcademicPeriod $period): void
    {
        $this->command->info('📝 Inscribiendo estudiantes...');

        // Distribución de estudiantes por sección
        $distribution = [
            0 => [0, 1, 2, 3, 4, 5, 6, 7],     // 4to A: primeros 8 estudiantes
            1 => [8, 9, 10, 11],                // 4to B: siguientes 4
            2 => [12, 13, 14, 15],              // 5to A: siguientes 4
            4 => [16, 17, 18, 19],              // 3ro A: últimos 4
        ];

        foreach ($distribution as $sectionIndex => $studentIndices) {
            if (!isset($sections[$sectionIndex])) continue;
            
            $section = $sections[$sectionIndex];
            foreach ($studentIndices as $studentIndex) {
                if (!isset($students[$studentIndex])) continue;
                
                Enrollment::firstOrCreate(
                    [
                        'student_id' => $students[$studentIndex]->id,
                        'section_id' => $section->id,
                        'academic_period_id' => $period->id,
                    ],
                    [
                        'enrollment_date' => $period->start_date,
                        'status' => 'active',
                    ]
                );
            }
        }
    }

    private function createSubjectAssignments(array $teachers, array $sections, AcademicPeriod $period): array
    {
        $this->command->info('📚 Creando asignaciones profesor-materia...');

        $subjects = Subject::all();
        $assignments = [];

        // Asignar materias a profesores por sección
        foreach ($sections as $section) {
            foreach ($subjects->take(6) as $index => $subject) {
                $teacher = $teachers[$index % count($teachers)];
                
                $assignment = SubjectAssignment::firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'section_id' => $section->id,
                        'academic_period_id' => $period->id,
                    ],
                    [
                        'status' => true,
                    ]
                );
                $assignments[] = $assignment;
            }
        }

        return $assignments;
    }

    private function createSchedules(array $assignments): void
    {
        $this->command->info('📅 Creando horarios...');

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $timeSlots = [
            ['07:00', '07:45'],
            ['07:45', '08:30'],
            ['08:45', '09:30'],
            ['09:30', '10:15'],
            ['10:30', '11:15'],
            ['11:15', '12:00'],
        ];

        $classrooms = ['Aula 101', 'Aula 102', 'Aula 103', 'Aula 201', 'Aula 202', 'Lab. Computación'];

        // Agrupar asignaciones por sección
        $assignmentsBySection = [];
        foreach ($assignments as $assignment) {
            $sectionId = $assignment->section_id;
            if (!isset($assignmentsBySection[$sectionId])) {
                $assignmentsBySection[$sectionId] = [];
            }
            $assignmentsBySection[$sectionId][] = $assignment;
        }

        foreach ($assignmentsBySection as $sectionId => $sectionAssignments) {
            $slotIndex = 0;
            
            foreach ($days as $dayIndex => $day) {
                // 3-4 clases por día
                $classesPerDay = rand(3, 4);
                
                for ($i = 0; $i < $classesPerDay && $slotIndex < count($sectionAssignments); $i++) {
                    $assignment = $sectionAssignments[$slotIndex % count($sectionAssignments)];
                    $timeSlot = $timeSlots[$i % count($timeSlots)];
                    
                    Schedule::firstOrCreate(
                        [
                            'subject_assignment_id' => $assignment->id,
                            'day_of_week' => $day,
                            'start_time' => $timeSlot[0],
                        ],
                        [
                            'end_time' => $timeSlot[1],
                            'classroom' => $classrooms[array_rand($classrooms)],
                            'status' => true,
                        ]
                    );
                    
                    $slotIndex++;
                }
            }
        }
    }

    private function createTasks(array $assignments, AcademicPeriod $period): array
    {
        $this->command->info('📋 Creando tareas...');

        $terms = Term::where('academic_period_id', $period->id)->get();
        $tasks = [];

        $taskTemplates = [
            ['title' => 'Ejercicios de práctica', 'type' => 'homework', 'description' => 'Resolver los ejercicios del libro de texto, páginas 45-48.'],
            ['title' => 'Investigación', 'type' => 'project', 'description' => 'Investigar sobre el tema visto en clase y presentar un informe escrito.'],
            ['title' => 'Evaluación escrita', 'type' => 'exam', 'description' => 'Evaluación de los contenidos del lapso.'],
            ['title' => 'Trabajo en equipo', 'type' => 'project', 'description' => 'Realizar un trabajo grupal sobre el tema asignado.'],
            ['title' => 'Taller práctico', 'type' => 'homework', 'description' => 'Completar el taller de ejercicios prácticos.'],
        ];

        foreach ($assignments as $assignment) {
            // Crear 2-3 tareas por asignación para el primer lapso
            $term = $terms->first();
            if (!$term) continue;

            $numTasks = rand(2, 3);
            for ($i = 0; $i < $numTasks; $i++) {
                $template = $taskTemplates[array_rand($taskTemplates)];
                $dueDate = Carbon::parse($term->start_date)->addDays(rand(7, 60));
                
                $task = Task::firstOrCreate(
                    [
                        'subject_assignment_id' => $assignment->id,
                        'term_id' => $term->id,
                        'title' => $template['title'] . ' - ' . $assignment->subject->name,
                    ],
                    [
                        'description' => $template['description'],
                        'instructions' => 'Seguir las instrucciones del profesor. Entregar en formato digital o físico según se indique.',
                        'type' => $template['type'],
                        'max_score' => 20,
                        'weight' => rand(10, 25),
                        'due_date' => $dueDate,
                        'available_from' => Carbon::parse($term->start_date),
                        'is_published' => true,
                        'status' => true,
                    ]
                );
                $tasks[] = $task;
            }
        }

        return $tasks;
    }

    private function createTaskSubmissions(array $tasks, array $sections): void
    {
        $this->command->info('📤 Creando entregas de tareas...');

        foreach ($tasks as $task) {
            $assignment = $task->subjectAssignment;
            $section = $assignment->section;
            $enrollments = Enrollment::where('section_id', $section->id)->get();

            foreach ($enrollments as $enrollment) {
                // 70% de probabilidad de que el estudiante haya entregado
                if (rand(1, 100) <= 70) {
                    $submittedAt = Carbon::parse($task->available_from)->addDays(rand(1, 14));
                    
                    // 60% de probabilidad de estar calificado
                    $isGraded = rand(1, 100) <= 60;
                    
                    TaskSubmission::firstOrCreate(
                        [
                            'task_id' => $task->id,
                            'student_id' => $enrollment->student_id,
                        ],
                        [
                            'content' => 'Entrega del estudiante para la tarea asignada.',
                            'file_path' => null,
                            'submitted_at' => $submittedAt,
                            'score' => $isGraded ? rand(10, 20) : null,
                            'feedback' => $isGraded ? 'Buen trabajo. Sigue así.' : null,
                            'graded_at' => $isGraded ? $submittedAt->copy()->addDays(rand(1, 5)) : null,
                            'graded_by' => $isGraded ? $assignment->teacher_id : null,
                            'status' => $isGraded ? 'graded' : 'submitted',
                        ]
                    );
                }
            }
        }
    }

    private function createScores(array $assignments, array $sections, AcademicPeriod $period): void
    {
        $this->command->info('📊 Creando calificaciones...');

        $terms = Term::where('academic_period_id', $period->id)->get();
        $firstTerm = $terms->first();

        if (!$firstTerm) return;

        foreach ($assignments as $assignment) {
            $section = $assignment->section;
            $enrollments = Enrollment::where('section_id', $section->id)->get();

            foreach ($enrollments as $enrollment) {
                // Crear nota para el primer lapso
                StudentScore::firstOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'subject_assignment_id' => $assignment->id,
                        'term_id' => $firstTerm->id,
                    ],
                    [
                        'score' => rand(10, 20),
                        'observations' => null,
                        'graded_by' => $assignment->teacher_id,
                        'graded_at' => now(),
                        'is_final' => false,
                    ]
                );
            }
        }
    }

    private function createAttendance(array $sections, array $assignments, AcademicPeriod $period): void
    {
        $this->command->info('✅ Creando registros de asistencia...');

        $statuses = [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_ABSENT,
            Attendance::STATUS_EXCUSED,
        ];

        // Crear asistencia para las últimas 2 semanas
        $startDate = Carbon::now()->subWeeks(2)->startOfWeek();
        $endDate = Carbon::now();

        foreach ($sections as $section) {
            $enrollments = Enrollment::where('section_id', $section->id)->get();
            $sectionAssignments = SubjectAssignment::where('section_id', $section->id)->get();
            
            if ($sectionAssignments->isEmpty()) continue;
            
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                // Solo días de semana
                if ($currentDate->isWeekday()) {
                    $assignment = $sectionAssignments->random();
                    
                    foreach ($enrollments as $enrollment) {
                        Attendance::firstOrCreate(
                            [
                                'student_id' => $enrollment->student_id,
                                'section_id' => $section->id,
                                'date' => $currentDate->format('Y-m-d'),
                            ],
                            [
                                'subject_assignment_id' => $assignment->id,
                                'academic_period_id' => $period->id,
                                'recorded_by' => $assignment->teacher_id,
                                'status' => $statuses[array_rand($statuses)],
                                'notes' => null,
                            ]
                        );
                    }
                }
                
                $currentDate->addDay();
            }
        }
    }

    private function showDemoCredentials(): void
    {
        $this->command->info('🔐 CREDENCIALES DE DEMOSTRACIÓN:');
        $this->command->info('================================');
        $this->command->table(
            ['Rol', 'Email', 'Contraseña'],
            [
                ['🛡️  Administrador', 'admin@sabere.com', 'password'],
                ['🎓 Director', 'director@sabere.com', 'password'],
                ['📋 Coordinador', 'coordinador@sabere.com', 'password'],
                ['👨‍🏫 Profesor', 'profesor@sabere.com', 'password'],
                ['👨‍🎓 Estudiante', 'estudiante@sabere.com', 'password'],
                ['👨‍👩‍👧 Representante', 'representante@sabere.com', 'password'],
            ]
        );
        $this->command->info('');
        $this->command->info('📊 DATOS CREADOS:');
        $this->command->info('- 1 Período académico con 3 lapsos');
        $this->command->info('- 6 Profesores');
        $this->command->info('- 20 Estudiantes');
        $this->command->info('- 10 Representantes');
        $this->command->info('- 6 Secciones (3ro, 4to y 5to grado)');
        $this->command->info('- Horarios completos');
        $this->command->info('- Tareas con entregas');
        $this->command->info('- Calificaciones del primer lapso');
        $this->command->info('- Registros de asistencia');
    }
}
