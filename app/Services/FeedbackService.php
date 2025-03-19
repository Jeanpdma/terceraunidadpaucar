<?php

namespace App\Services;

use App\Models\Tarea;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function agregarFeedback(int $tareaId, int $usuarioId, string $comentario, $archivo = null): Feedback
    {
        $tarea = Tarea::findOrFail($tareaId);
        $fileSizeLimit = $tarea->file_size_limit ?: config('app.max_file_size', 10240);
        DB::beginTransaction();
        try {
            $feedback = new Feedback([
                'tarea_id' => $tareaId,
                'usuario_id' => $usuarioId,
                'comentario' => $comentario,
            ]);

            if ($archivo) {
                if ($archivo->getSize() > $fileSizeLimit) {
                    throw new \Exception("El tamaño del archivo excede el límite permitido.");
                }

                $nombreArchivo = time() . '_feedback_' . $archivo->getClientOriginalName();
                $ruta = $archivo->storeAs('feedback_archivos', $nombreArchivo, 'public');
                $feedback->archivo_adjunto = $ruta;
            }

            $feedback->save();
            DB::commit();

            return $feedback->load('usuario');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function obtenerFeedback(int $tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);
        return $tarea->feedback()->with('usuario')->orderBy('created_at', 'desc')->get();
    }

    public function editarFeedback(int $feedbackId, string $comentario, $archivo = null): Feedback
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $tarea = $feedback->tarea; // Get the task associated with the feedback
        $fileSizeLimit = $tarea->file_size_limit ?: config('app.max_file_size', 10240);

        DB::beginTransaction();
        try {
            $feedback->comentario = $comentario;

            if ($archivo) {
                if ($archivo->getSize() > $fileSizeLimit) {
                    throw new \Exception("El tamaño del archivo excede el límite permitido.");
                }
                // Eliminar archivo anterior si existe
                if ($feedback->archivo_adjunto) {
                    Storage::disk('public')->delete($feedback->archivo_adjunto);
                }

                $nombreArchivo = time() . '_feedback_' . $archivo->getClientOriginalName();
                $ruta = $archivo->storeAs('feedback_archivos', $nombreArchivo, 'public');
                $feedback->archivo_adjunto = $ruta;
            }

            $feedback->save();
            DB::commit();

            return $feedback->load('usuario');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
