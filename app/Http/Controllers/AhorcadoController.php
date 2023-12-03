<?php

namespace App\Http\Controllers;

use App\Models\Ahorcado;
use App\Models\Ganador;
use App\Models\Juego;
use App\Models\Jugador;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class AhorcadoController extends Controller
{
    public function iniciarJuego()
    {
        try {
            // Validar que no existan jugadores
            $cantidad_jugadores = Jugador::count();
            if ($cantidad_jugadores > 0) {
                // Si existen jugadores, eliminarlos
                Jugador::truncate();
            }

            // Validar que no exista un juego en curso
            $cantidad_ahorcados = Ahorcado::count();
            if ($cantidad_ahorcados > 0) {
                // Si existe un juego en curso, eliminarlo
                Ahorcado::truncate();
            }

            // Validar que no exista un ganador
            $cantidad_ganadores = Ganador::count();
            if ($cantidad_ganadores > 0) {
                // Si existe un ganador, eliminarlo
                Ganador::truncate();
            }

            // Valida que no haya un juego en curso
            $juego = Juego::count();
            if ($juego > 0) {
                // Si existe un juego en curso, eliminarlo
                Juego::truncate();
            }

            // Inicia un juego
            $juego = new Juego();
            $juego->estado = false;
            $juego->save();

            return Response::json([
                'status' => 'success',
                'message' => 'Juego en curso'
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getLine() . ' - ' . $exception->getFile());
            return Response::json([
                'status' => 'error',
                'message' => 'Error En La Generación De La Solicitud'
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }

    public function inscribirse(Request $request)
    {
        try {
            // Validacion
            $rules = [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:jugadores,nombre'
                ]
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio',
                'string' => 'El campo :attribute debe ser un texto',
                'max' => 'El campo :attribute debe tener un máximo de :max caracteres',
                'unique' => 'Ese nombre ya ha sido registrado'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Datos Recibidos Incorrectos',
                    'errors' => $validator->messages()
                ], 400, [], JSON_PRETTY_PRINT);
            }

            // Validar la cantidad Máxima de jugadores
            $cantidad_jugadores = env('CANTIDAD_MAXIMA_JUGADORES');
            $cantidad_jugadores_Actual = Jugador::count();
            if ($cantidad_jugadores == $cantidad_jugadores_Actual) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Se ha alcanzado la cantidad máxima de jugadores'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            // Validar que se pueda registrar
            $juego = Juego::count();

            if (!$juego > 0) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'No existe un juego en curso'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            $medidaPalabraSecreta = strlen(env('PALABRA_SECRETA'));
            $fraseConGuiones = str_repeat('-', $medidaPalabraSecreta);

            // Valida el mayoy turno registrado
            $mayorTurno = Ahorcado::max('turno');

            if ($mayorTurno == null) {
                $mayorTurno = 1;
                $turno = true;
            } else {
                $mayorTurno++;
            }

            // Validar si ya existe un jugador almenos
            $jugador = Jugador::count();
            if ($jugador > 0) {
                $turno = false;
            }

            // Registrar jugador
            $jugador = new Jugador();
            $jugador->nombre = $request->nombre;
            $jugador->intentos_restantes = 3;
            $jugador->turno = $turno;
            $jugador->estado = true;
            $jugador->frase = $fraseConGuiones;
            $jugador->save();

            // Registrar ahorcado
            $ahorcado = new Ahorcado();
            $ahorcado->jugador_id = $jugador->id;
            $ahorcado->turno = $mayorTurno;
            $ahorcado->save();

            $cantidad_jugadores = env('CANTIDAD_MAXIMA_JUGADORES');
            $cantidad_jugadores = (int)$cantidad_jugadores;
            $cantidad_jugadores_Actual = Jugador::count();
            if ($cantidad_jugadores == $cantidad_jugadores_Actual) {
                // iniciar el juego
                $juego = Juego::first();
                $juego->estado = true;
                $juego->save();
            }

            return Response::json([
                'status' => 'success',
                'message' => 'Jugador registrado correctamente con el id: ' . $jugador->id,
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getLine() . ' - ' . $exception->getFile());
            return Response::json([
                'status' => 'error',
                'message' => 'Error En La Generación De La Solicitud'
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }

    public function empezarJuego()
    {
        try {
            // Validar que existan jugadores
            $cantidad_jugadores = Jugador::count();
            if ($cantidad_jugadores == 0) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'No existen jugadores registrados'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            // Valida si ya existe un juego en curso
            $juego = Juego::count();
            if (!$juego > 0) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'No se inició el juego en ningún momento'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            // iniciar el juego
            $juego = Juego::first();
            $juego->estado = true;
            $juego->save();

            // Obtener el jugador con el turno más bajo
            $jugador = Jugador::orderBy('turno', 'asc')->first();

            return Response::json([
                'status' => 'success',
                'message' => 'Juego iniciado, es el turno del jugador: ' . $jugador->nombre,
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getLine() . ' - ' . $exception->getFile());
            return Response::json([
                'status' => 'error',
                'message' => 'Error En La Generación De La Solicitud'
            ], 500, [], JSON_PRETTY_PRINT);
        }
    }

    /* public function iniciarJuego()
    {
        try {
            // Validar que existan jugadores
            $cantidad_jugadores = Jugador::count();
            if ($cantidad_jugadores == 0) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'No existen jugadores registrados'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            // Valida si ya existe un juego en curso
            $juego = Juego::first();
            if ($juego != null) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Ya existe un juego en curso'
                ], 400, [], JSON_PRETTY_PRINT);
            }

            

            // Obtener el jugador con el turno más bajo
            $jugador = Jugador::orderBy('turno', 'asc')->first();

            return Response::json([
                'status' => 'success',
                'message' => 'Juego iniciado, es el turno del jugador: ' . $jugador->nombre,
            ], 200, [], JSON_PRETTY_PRINT);
        } catch (Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getLine() . ' - ' . $exception->getFile());
            return Response::json([
                'status' => 'error',
                'message' => 'Error En La Generación De La Solicitud'
            ], 500, [], JSON_PRETTY_PRINT);
        }
    } */

    public function jugar(Request $request, $id)
    {
        try {
            // Validacion
            $rules = [
                'letra' => [
                    'required',
                    'string',
                ]
            ];
            $messages = [
                'required' => 'El campo :attribute es obligatorio',
                'string' => 'El campo :attribute debe ser un texto',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return Response::json([
                    'status' => 'error',
                    'message' => 'Datos Recibidos Incorrectos',
                    'errors' => $validator->messages()
                ], 400, [], JSON_PRETTY_PRINT);
            }

            $this->validarGanador();

            $this->validarExistenciaJugadores();

            // Validar el jugador con turno en true
            $jugador = Jugador::find($id);

            $this->validarExistenciaJugador($jugador);

            $this->validarTurnoEIntentos($jugador);

            // Validar el tamaño de la letra
            $tamañoLetra = strlen($request->letra);

            // Si el tamaño es mayor a 1, se valida que sea una palabra
            if ($tamañoLetra > 1) {

                $responde = $this->validarPalabra($request, $jugador);
            }

            // Si el tamaño es 1, se valida que la letra no haya sido ingresada anteriormente
            if ($tamañoLetra == 1) {

                $responde = $this->validarLetra($request, $jugador);
            }

            return $responde;

        } catch (Exception $exception) {
            Log::error($exception->getMessage() . ' - ' . $exception->getLine() . ' - ' . $exception->getFile());

            return Response::json([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], $exception->getCode(), [], JSON_PRETTY_PRINT);
        }
    }

    public function validarGanador()
    {
        // validar si ya existe un ganador
        $ganador = Ganador::first();
        if ($ganador != null) {
            throw new Exception('El ganador fué: ' . $ganador->nombre . ' con la palabra: ' . $ganador->palabra, 400);
        }
    }

    public function validarExistenciaJugadores()
    {
        // Validar que existan jugadores
        $cantidad_jugadores = Jugador::count();
        if ($cantidad_jugadores == 0) {
            throw new Exception('No existen jugadores registrados', 400);
        }
    }

    public function validarExistenciaJugador($jugador)
    {
        // Validar que el jugador exista
        if ($jugador == null) {
            throw new Exception('No existe el jugador', 400);
        }
    }

    public function validarTurnoEIntentos($jugador)
    {
        // Validar que el jugador tenga el turno en true
        if ($jugador->turno == false) {
            throw new Exception('No es el turno del jugador', 400);
        }

        // Validar que tenga intentos restantes
        if ($jugador->intentos_restantes == 0) {
            throw new Exception('El jugador no tiene intentos restantes', 400);
        }
    }

    public function validarPalabra($request, $jugador)
    {
        $letra = $request->letra;


        // Validar que la palabra ingresada sea igual a la palabra secreta
        $palabraSecreta = env('PALABRA_SECRETA');
        if ($palabraSecreta != $letra) {
            // Si la palabra ingresada no es igual a la palabra secreta, se resta un intento
            $jugador->intentos_restantes = 0;
            $jugador->save();

            // Se valida si el jugador se quedó sin intentos
            if ($jugador->intentos_restantes == 0) {
                // Si el jugador se quedó sin intentos, se finaliza el juego
                $jugador->estado = false;
                $jugador->save();

                // Se actualiza el turno del siguiente jugador
                $siguienteJugador = Jugador::where('turno', false)->first();
                $siguienteJugador->turno = true;
                $siguienteJugador->save();

                return Response::json([
                    'status' => 'success',
                    'message' => 'Juego finalizado',
                    'data' => [
                        'turno' => $siguienteJugador->turno,
                        'palabra' => $siguienteJugador->frase,
                        'intentos_restantes' => $siguienteJugador->intentos_restantes
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            }
        } else {
            // Se actualiza el estado a false de todos los jugadores
            $jugadores = Jugador::all();
            foreach ($jugadores as $jugador) {
                $jugador->estado = false;
                $jugador->save();
            }

            // Se registra el ganador
            $ganador = new Ganador();
            $ganador->nombre = $jugador->nombre;
            $ganador->palabra = $palabraSecreta;
            $ganador->save();

            // Desactivar el juego en curso
            $juego = Juego::first();
            $juego->estado = false;
            $juego->save();

            return Response::json([
                'status' => 'success',
                'message' => 'Has ganado el juego, la palabra secreta es: ' . $palabraSecreta,
            ], 200, [], JSON_PRETTY_PRINT);
        }
    }

    public function validarLetra($request, $jugador)
    {
        $frase = $jugador->frase;
        $letra = $request->letra;

        // Validar que la letra no haya sido ingresada anteriormente
        if (strpos($frase, $letra) !== false) {
            throw new Exception('La letra ya fue ingresada anteriormente', 400);
        }

        // Validar que la letra ingresada se encuentre en la palabra secreta
        $palabraSecreta = env('PALABRA_SECRETA');
        $letra = $request->letra;

        if (strpos($palabraSecreta, $letra) !== false) {
            // Si la letra se encuentra en la palabra secreta, se reemplaza en la frase en esa posición o posiciones
            $frase = $jugador->frase;
            $palabraSecreta = str_split($palabraSecreta);
            $frase = str_split($frase);
            foreach ($palabraSecreta as $key => $value) {
                if ($value == $letra) {
                    $frase[$key] = $letra;
                }
            }
            $frase = implode('', $frase);

            // Se actualiza la frase del jugador
            $jugador->frase = $frase;
            $jugador->save();

            // Se valida si la frase ya está completa
            if (strpos($frase, '-') === false) {
                // Si la frase ya está completa, se finaliza el juego
                $jugador->estado = false;
                $jugador->save();

                // Se actualiza el estado a false de todos los jugadores
                $jugadores = Jugador::all();
                foreach ($jugadores as $jugador) {
                    $jugador->estado = false;
                    $jugador->save();
                }

                // Después de finalizar el juego, se registra el ganador
                $ganador = new Ganador();
                $ganador->nombre = $jugador->nombre;
                $ganador->palabra = $palabraSecreta;
                $ganador->save();

                // Desactivar el juego en curso
                $juego = Juego::first();
                $juego->estado = false;
                $juego->save();

                return Response::json([
                    'status' => 'success',
                    'message' => 'Juego finalizado, has ganado, la palabra secreta es: ' . $palabraSecreta,
                ], 200, [], JSON_PRETTY_PRINT);
            }

            // Se actualiza el turno del jugador
            $jugador->turno = false;
            $jugador->save();

            // Se actualiza el turno del siguiente jugador
            $siguienteJugador = Jugador::where('turno', false)->first();
            $siguienteJugador->turno = true;
            $siguienteJugador->save();

            return Response::json([
                'status' => 'success',
                'message' => 'Letra correcta, este es el estado de la plabra secreta: ' . $jugador->frase,
            ], 200, [], JSON_PRETTY_PRINT);
        } else {
            // Si la letra no se encuentra en la palabra secreta, se resta un intento
            $jugador->intentos_restantes--;
            $jugador->save();

            // Se valida si el jugador se quedó sin intentos
            if ($jugador->intentos_restantes == 0) {
                // Si el jugador se quedó sin intentos, se finaliza el juego
                $jugador->estado = false;
                $jugador->save();

                // Se actualiza el turno del siguiente jugador
                $siguienteJugador = Jugador::where('turno', false)->first();
                $siguienteJugador->turno = true;
                $siguienteJugador->save();

                return Response::json([
                    'status' => 'success',
                    'message' => 'Juego finalizado',
                    'data' => [
                        'turno' => $siguienteJugador->turno,
                        'palabra' => $siguienteJugador->frase,
                        'intentos_restantes' => $siguienteJugador->intentos_restantes
                    ]
                ], 200, [], JSON_PRETTY_PRINT);
            }

            return Response::json([
                'status' => 'success',
                'message' => 'Letra incorrecta, intentos restantes: ' . $jugador->intentos_restantes,
            ], 200, [], JSON_PRETTY_PRINT);
        }
    }
}
