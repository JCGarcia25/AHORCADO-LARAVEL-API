# Repositorio hecho en Laravel - AHORCADO

Su funcionamiento base es enfocado a microservidor servidos como API REST, este repositorio está enfocado al uso de contenedores
y para hacer uso del mismo debes tener instalado en tu maquina la herramienta DOCKER, en mi caso Docker version 24.0.7.

Para hacer uso del mismo debes seguir los siguientes pasos; Estando parado sobre el directorio que contiene este repositorio ejecutar:

1. docker compose buid

2. docker compose up -d

3. docker exec -it ahorcado-app bash

## El comando anterior debió haberte puesto dentro del contendor de la app

4. cp .env.example .env

5. composer install

6. composer update

7. php artisan key:generate

8. php artisan storage:link

9. php artisan migrate

10. chmod 777 -R storage

11. exit

Con todo lo anteior ejecutado será suficiente para poder realizar peticiones vía CURL a nuestra aplicación.

En caso de querer probar la puesta en marcha de nuestra app, podemos acceder en nuestro navegador a http://localhost:81/

## Pasos para jugar ahorcado

El juego consiste de 4 microservicios: iniciarJuego, inscribirse, empezarJuego, jugar.

Cada uno de los anteriores deben ejecutarse en ese orden, el fin de estos es, preparar el juego, inscribir jugadores, una vez estén todos, o se ocupe
la cantidad máxima de estos, empezará el juego, y por ultimo, jugar en orden de turnos

#### Microsercicio para pregarar el juego
1. GET http://localhost:81/api/iniciarJuego

#### Microsercicio para inscribir cada jugador
2. POST http://localhost:81/api/inscribirse

    Body:
    {
        "nombre": "jack"
    }

#### Microsercicio para inscribir cada jugador
3. GET http://localhost:81/api/empezarJuego    ->  Tener en cuenta que no es necesario ejecutar este si ya se alcanzó la cantidad máxima de jugadores

### Microservicio para jugar
4. POST http://localhost:81/api/jugar/{id}

    Body:
    {
    "letra": "H"
    }

## Tener en cuenta
Para jugar, en el espacio que dice "id", debe ir el id entregado por el sistema al inscribirse en el juego. En el body tenemos 2 opciones de juego:

1. Jugar una letra: En este caso se evaluará si la letra existe en la palabra secreta

2. Jugar una palabra: esta es una única oportunidad donde se comparará con la palabra secreta, es ganar o perder

3. PALABRA_SECRETA y CANTIDAD_MAXIMA_JUGADORES estám definidas dentro del archivo de variables de entorno
