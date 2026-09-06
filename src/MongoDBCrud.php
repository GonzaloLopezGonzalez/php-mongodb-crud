<?php

namespace App;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\BSON\ObjectId;
use Exception;

class MongoDBCrud
{
    private Database $db;
    private Collection $collection;

    public function __construct(string $uri, string $database, string $collection)
    {
        try {
            $client = new Client($uri);
            $this->db = $client->selectDatabase($database);
            $this->collection = $this->db->selectCollection($collection);
        } catch (Exception $e) {
            throw new Exception("Error al conectar con MongoDB: " . $e->getMessage());
        }
    }

    /**
     * Insertar un documento
     */
    public function create(array $data): string
    {
        $result = $this->collection->insertOne($data);
        return (string) $result->getInsertedId();
    }

    /**
     * Insertar varios documentos
     */
    public function createMany(array $documents): array
    {
        $result = $this->collection->insertMany($documents);
        return array_map('strval', $result->getInsertedIds());
    }

    /**
     * Buscar todos los documentos
     */
    public function findAll(array $filter = [], array $options = []): array
    {
        return $this->collection->find($filter, $options)->toArray();
    }

    /**
     * Buscar un documento por ID
     */
    public function findById(string $id): ?array
    {
        if (!ObjectId::isValid($id)) {
            return null;
        }

        $document = $this->collection->findOne(['_id' => new ObjectId($id)]);
        return $document ? (array) $document : null;
    }

    /**
     * Buscar un documento con filtro
     */
    public function findOne(array $filter): ?array
    {
        $document = $this->collection->findOne($filter);
        return $document ? (array) $document : null;
    }

    /**
     * Actualizar un documento por ID
     */
    public function update(string $id, array $data): int
    {
        if (!ObjectId::isValid($id)) {
            return 0;
        }

        $result = $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );

        return $result->getModifiedCount();
    }

    /**
     * Actualizar varios documentos
     */
    public function updateMany(array $filter, array $data): int
    {
        $result = $this->collection->updateMany(
            $filter,
            ['$set' => $data]
        );

        return $result->getModifiedCount();
    }

    /**
     * Eliminar un documento por ID
     */
    public function delete(string $id): int
    {
        if (!ObjectId::isValid($id)) {
            return 0;
        }

        $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
        return $result->getDeletedCount();
    }

    /**
     * Eliminar varios documentos
     */
    public function deleteMany(array $filter): int
    {
        $result = $this->collection->deleteMany($filter);
        return $result->getDeletedCount();
    }

    /**
     * Contar documentos
     */
    public function count(array $filter = []): int
    {
        return $this->collection->countDocuments($filter);
    }

    /**
     * Crear una colección
     */
    public function crearColeccion(string $nombreColeccion, array $opciones = []): bool
    {
        try {
            $this->db->createCollection($nombreColeccion, $opciones);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al crear la colección: " . $e->getMessage());
        }
    }

    /**
     * Eliminar una colección por completo
     */
    public function eliminarColeccion(string $nombreColeccion): bool
    {
        try {
            $this->db->dropCollection($nombreColeccion);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al eliminar la colección: " . $e->getMessage());
        }
    }
}