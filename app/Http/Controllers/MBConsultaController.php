<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MBConsultaController extends Controller
{
    public function validarUsuario(Request $request)
    {
        // Log para identificar que el método fue llamado
        Log::info('Iniciando validación de usuario', ['data' => $request->all()]);

        // Verificar si el header 'Authorization' está presente
        $authorizationHeader = $request->header('Authorization');
        if (!$authorizationHeader || $authorizationHeader !== config('banking.token_key')) {
            Log::warning('Token de autorización inválido o ausente', ['header' => $authorizationHeader]);
            return response()->json(['status' => false, 'error' => 'Token inválido'], 401);
        }

        // Validar que los datos necesarios estén presentes
        try {
            $request->validate([
                'IdCliente' => 'required|string|size:8',
                'Monto' => 'nullable|regex:/^\d+(\.\d{1,2})?$/',
                'TelefonoComercio' => 'required|string|size:11',
            ]);
        } catch (\Exception $e) {
            Log::error('Error en la validación de datos', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'error' => 'Datos inválidos'], 400);
        }

        // Log para confirmar que los datos fueron validados
        Log::info('Validación de datos completada', ['data' => $request->all()]);

        // Lista de IPs permitidas
        //$whitelistedIps = ['45.175.213.98', '200.74.203.91', '190.202.123.66'];
//
        //// Validar IP
        //if (!in_array($request->ip(), $whitelistedIps)) {
        //    Log::warning('IP no permitida', ['ip' => $request->ip()]);
        //    return response()->json(['status' => false, 'error' => 'IP no permitida'], 403);
        //}

        // Lógica de validación del cliente
        try {
            $clienteValido = \DB::table('users')->where('code', $request->IdCliente)->exists();
            Log::info('Resultado de la consulta del cliente', ['IdCliente' => $request->IdCliente, 'exists' => $clienteValido]);
        } catch (\Exception $e) {
            Log::error('Error al consultar la base de datos', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'error' => 'Error interno del servidor'], 500);
        }

        // Respuesta según la validación
        $status = (bool) $clienteValido;
        Log::info('Finalizando validación', ['status' => $status]);

        return response()->json(['status' => $status]);
    }
}
