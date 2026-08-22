<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Seeder;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Perfil de Usuario', 'slug' => 'perfil', 'description' => 'Gestiona tu información personal', 'icon' => 'user', 'sort_order' => 1],
            ['name' => 'Notas y Calificaciones', 'slug' => 'notas', 'description' => 'Consulta de calificaciones y lapsos', 'icon' => 'clipboard', 'sort_order' => 2],
            ['name' => 'Horarios', 'slug' => 'horarios', 'description' => 'Visualización de horarios de clase', 'icon' => 'clock', 'sort_order' => 3],
            ['name' => 'Tareas y Asignaciones', 'slug' => 'tareas', 'description' => 'Entrega de tareas y asignaciones', 'icon' => 'book-open', 'sort_order' => 4],
            ['name' => 'Circulares', 'slug' => 'circulares', 'description' => 'Comunicados de la institución', 'icon' => 'bell', 'sort_order' => 5],
            ['name' => 'Reinscripciones', 'slug' => 'reinscripciones', 'description' => 'Proceso de reinscripción estudiantil', 'icon' => 'refresh-cw', 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            HelpCategory::create($cat);
        }

        $articles = [
            ['category_id' => 1, 'title' => 'Cómo actualizar mi foto de perfil', 'slug' => 'actualizar-foto-perfil', 'content' => 'Dirígete a "Mi Perfil" en el menú lateral. Allí encontrarás la sección "Foto de Perfil" donde puedes subir una imagen en formato JPG, PNG o WebP de hasta 2MB.', 'role_target' => 'all', 'sort_order' => 1],
            ['category_id' => 1, 'title' => 'Actualizar información de contacto', 'slug' => 'actualizar-contacto', 'content' => 'En la sección "Mi Perfil" puedes modificar tu nombre, correo electrónico, teléfono, dirección y contacto de emergencia.', 'role_target' => 'all', 'sort_order' => 2],
            ['category_id' => 2, 'title' => 'Ver mis calificaciones', 'slug' => 'ver-calificaciones', 'content' => 'Los estudiantes pueden consultar sus calificaciones ingresando al menú "Mis Notas". Allí verás los tres lapsos con sus respectivas calificaciones.', 'role_target' => 'student', 'sort_order' => 1],
            ['category_id' => 2, 'title' => 'Cómo registrar notas', 'slug' => 'registrar-notas', 'content' => 'Los profesores acceden al menú "Calificaciones", seleccionan la materia y el lapso, y pueden ingresar las notas individualmente o en bloque.', 'role_target' => 'teacher', 'sort_order' => 2],
            ['category_id' => 3, 'title' => 'Visualizar horario de clases', 'slug' => 'ver-horario', 'content' => 'Accede a "Horarios" desde el menú principal para ver tu horario semanal con las materias asignadas y los intervalos de recreo.', 'role_target' => 'all', 'sort_order' => 1],
            ['category_id' => 4, 'title' => 'Crear una tarea', 'slug' => 'crear-tarea', 'content' => 'Desde el panel del profesor, selecciona "Tareas", elige la materia y luego "Nueva Tarea". Completa el título, descripción, fecha de entrega y materiales.', 'role_target' => 'teacher', 'sort_order' => 1],
            ['category_id' => 4, 'title' => 'Entregar una tarea', 'slug' => 'entregar-tarea', 'content' => 'En "Mis Tareas" verás las asignaciones pendientes. Haz clic en "Entregar" y adjunta el archivo o escribe tu respuesta antes de la fecha límite.', 'role_target' => 'student', 'sort_order' => 2],
            ['category_id' => 5, 'title' => 'Leer circulares', 'slug' => 'leer-circulares', 'content' => 'Las circulares se encuentran en el menú "Circulares". Las no leídas se marcan con un indicador "Nuevo". Haz clic para leer el contenido completo.', 'role_target' => 'all', 'sort_order' => 1],
            ['category_id' => 6, 'title' => 'Proceso de reinscripción', 'slug' => 'proceso-reinscripcion', 'content' => 'Los representantes pueden acceder a "Reinscripciones" desde su panel, seleccionar el estudiante y completar el formulario de solicitud.', 'role_target' => 'guardian', 'sort_order' => 1],
        ];

        foreach ($articles as $art) {
            HelpArticle::create($art);
        }
    }
}
