<?php

require __DIR__ . '/vendor/autoload.php';

use App\MongoDBCrud;
use MongoDB\BSON\UTCDateTime;

try {
    // La conexion y la base de datos se configuran en config.php.
    $config = require __DIR__ . '/config.php';

    $crud = new MongoDBCrud(
        $config['mongodb_uri'],
        $config['mongodb_database'],
        'usuarios'
    );

    echo "=== EJEMPLO CRUD CON MongoDBCrud ===\n\n";

    // CREATE: insertar un documento.
    $id = $crud->create([
        'nombre' => 'Ana Garcia',
        'email' => 'ana@ejemplo.com',
        'edad' => 25,
        'activo' => true,
        'roles' => ['usuario'],
        'creado_en' => new UTCDateTime(),
    ]);
    echo "Documento insertado. ID: {$id}\n";

    // CREATE MANY: insertar varios documentos.
    $ids = $crud->createMany([
        ['nombre' => 'Luis Perez', 'email' => 'luis@ejemplo.com', 'edad' => 32, 'activo' => true],
        ['nombre' => 'Maria Lopez', 'email' => 'maria@ejemplo.com', 'edad' => 28, 'activo' => false],
    ]);
    echo 'Documentos insertados: ' . count($ids) . "\n\n";

    // READ: buscar documentos con un filtro y opciones del driver.
    echo "Usuarios mayores de 26 años:\n";
    $usuarios = $crud->findAll(
        ['edad' => ['$gt' => 26]],
        ['sort' => ['nombre' => 1]]
    );

    foreach ($usuarios as $usuario) {
        echo "- {$usuario['nombre']} ({$usuario['edad']} años)\n";
    }

    // READ: buscar por un campo y por ObjectId.
    $usuario = $crud->findOne(['email' => 'ana@ejemplo.com']);
    $usuarioPorId = $crud->findById($id);
    echo "\nUsuario por email: " . ($usuario['nombre'] ?? 'no encontrado') . "\n";
    echo 'Usuario por ID: ' . ($usuarioPorId['nombre'] ?? 'no encontrado') . "\n";

    // COUNT: contar documentos.
    echo "Total de usuarios: {$crud->count()}\n";

    // UPDATE: actualizar un documento por su ObjectId.
    $modificados = $crud->update($id, [
        'edad' => 26,
        'actualizado_en' => new UTCDateTime(),
    ]);
    echo "Documentos modificados: {$modificados}\n";

    // UPDATE MANY: actualizar varios documentos.
    $activados = $crud->updateMany(['activo' => false], ['activo' => true]);
    echo "Documentos activados: {$activados}\n";

    // DELETE: eliminar el documento creado al principio.
    $eliminados = $crud->delete($id);
    echo "Documentos eliminados: {$eliminados}\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
