<?php

namespace App;

use Exception;
use MongoDB\Client;

class MongoDBDatabaseManager
{
    private Client $client;

    public function __construct(string $uri)
    {
        try {
            $this->client = new Client($uri);
        } catch (Exception $e) {
            throw new Exception('Error al conectar con MongoDB: ' . $e->getMessage());
        }
    }

    /**
     * Crea una base de datos creando su primera coleccion.
     */
    public function crearBaseDatos(
        string $nombreBaseDatos,
        string $nombreColeccion = 'coleccion_inicial'
    ): bool {
        try {
            $database = $this->client->selectDatabase($nombreBaseDatos);
            $database->createCollection($nombreColeccion);

            return true;
        } catch (Exception $e) {
            throw new Exception('Error al crear la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una base de datos y todas sus colecciones y documentos.
     */
    public function eliminarBaseDatos(string $nombreBaseDatos): bool
    {
        try {
            $database = $this->client->selectDatabase($nombreBaseDatos);
            $database->command(['dropDatabase' => 1]);

            return true;
        } catch (Exception $e) {
            throw new Exception('Error al eliminar la base de datos: ' . $e->getMessage());
        }
    }
}