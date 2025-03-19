<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Tarea;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Obtener proyectos y tareas del usuario autenticado
        $proyectos = $user->proyectos;
        $tareas = Tarea::whereHas('usuarios', function($query) use ($user) {
            $query->where('usuario_id', $user->id);
        })->get();

        // Conteos generales
        $totalProyectos = $proyectos->count();
        $totalTareas = $tareas->count();
        $tareasCompletadas = $tareas->where('estado', 'completada')->count();
        $tareasPendientes = $tareas->where('estado', '!=', 'completada')->count();

        // Proyectos próximos a vencer (en los próximos 7 días)
        $proyectosProximos = $proyectos->where('fecha_fin', '>', now())
                                       ->where('fecha_fin', '<=', now()->addDays(7));

        // Proyectos atrasados (fecha_fin anterior a hoy)
        $proyectosAtrasados = $proyectos->where('fecha_fin', '<', now());

        // Pasar todas las variables a la vista
        return view('dashboard', compact(
            'totalProyectos',
            'totalTareas',
            'tareasCompletadas',
            'tareasPendientes',
            'proyectos',
            'proyectosProximos',
            'proyectosAtrasados' // Agregamos esta variable
        ));
    }
}
