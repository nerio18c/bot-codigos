<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CodigoController extends Controller
{
    public function buscar(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'plataforma' => 'nullable|in:netflix,prime,disney',
        ]);

        $windowMinutes = (int) env('PIN_WINDOW_MINUTES', 60);

        // Buscamos el codigo que no tenga mas de X minutos de antiguedad
        $query = DB::table('codigos_streaming')
            ->where('email_cuenta', trim($request->correo))
            ->whereRaw('created_at >= (UTC_TIMESTAMP() - INTERVAL ? MINUTE)', [$windowMinutes]);

        if ($request->filled('plataforma')) {
            $query->where('plataforma', $request->plataforma);
        }

        $registro = $query->latest()->first();

        if ($registro) {
            return response()->json([
                'status' => 'success',
                'pin' => $registro->pin,
                'plataforma' => $registro->plataforma,
                'hora' => Carbon::parse($registro->created_at, 'UTC')
                    ->setTimezone(env('APP_TIMEZONE', 'America/Lima'))
                    ->format('h:i A'),
                'hace' => Carbon::parse($registro->created_at, 'UTC')
                    ->setTimezone(env('APP_TIMEZONE', 'America/Lima'))
                    ->locale('es')
                    ->diffForHumans()
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No se encontro un codigo reciente. Solicita uno nuevo en la app.'
        ], 404);
    }
}
