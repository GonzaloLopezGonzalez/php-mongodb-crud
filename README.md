# MongoDB CRUD para PHP

Este directorio contiene una pequeña libreria para trabajar con MongoDB desde PHP. Usa el driver oficial y organiza el codigo en dos clases con responsabilidades diferentes:

- `MongoDBCrud`: inserta, consulta, actualiza y elimina documentos de una coleccion.
- `MongoDBDatabaseManager`: administra bases de datos, por ejemplo, creando su primera coleccion o eliminando la base de datos completa.

## ¿Por que dos clases?

Es preferible mantener dos clases porque trabajar con documentos y administrar bases de datos son responsabilidades distintas. Esta separacion hace que cada clase sea mas facil de entender, probar y reutilizar:

- `MongoDBCrud` se usa en la logica habitual de una aplicacion.
- `MongoDBDatabaseManager` se reserva para tareas administrativas y operaciones que requieren mas permisos.
- Una aplicacion puede usar `MongoDBCrud` sin tener permisos para eliminar bases de datos.
- El borrado de una base de datos queda aislado y es mas dificil ejecutarlo por accidente desde una operacion CRUD.

No se ha renombrado `MongoDBCrud.php` ni se ha mezclado todo en una sola clase porque una clase unica terminaria acumulando operaciones sobre documentos, colecciones y bases de datos. Para un ejemplo muy pequeño una sola clase funcionaria, pero dos clases ofrecen una estructura mas clara cuando el proyecto crece.

## Requisitos

- PHP 8.1 o superior.
- Composer.
- La extension PHP `mongodb` habilitada.
- Un servidor MongoDB accesible desde la aplicacion.

La dependencia principal esta definida en `composer.json`:

```json
"mongodb/mongodb": "^1.19 || ^2.0"
```

## Instalacion

Desde este directorio:

```bash
composer install
```

Comprueba la extension en Windows:

```bash
php -m | findstr mongodb
```

En Linux o macOS:

```bash
php -m | grep mongodb
```

## Configuracion de la conexion

El archivo `config.php` es la fuente de configuracion de la conexion. Debe devolver un array con estas dos claves:

```php
<?php

return [
    'mongodb_uri' => 'mongodb://root:password123@localhost:27017/?authSource=admin',
    'mongodb_database' => 'mi_app',
];
```

La URI puede cambiarse, por ejemplo, para una instalacion sin autenticacion:

```php
'mongodb_uri' => 'mongodb://127.0.0.1:27017',
```

No guardes credenciales reales en el control de versiones. El `config.php` incluido contiene valores locales de ejemplo; en un entorno real debe protegerse, excluirse de Git o generarse a partir de variables de entorno.

## Instanciar `MongoDBCrud` con `config.php`

El constructor recibe la URI, la base de datos y la coleccion:

```php
public function __construct(
    string $uri,
    string $database,
    string $collection
)
```

La forma recomendada en este proyecto es cargar `config.php` y utilizar sus valores al crear la instancia:

```php
require __DIR__ . '/vendor/autoload.php';

use App\MongoDBCrud;

$config = require __DIR__ . '/config.php';

$crud = new MongoDBCrud(
    $config['mongodb_uri'],
    $config['mongodb_database'],
    'usuarios'
);
```

No es necesario crear manualmente `MongoDB\Client` ni seleccionar la base de datos en el archivo que utiliza la clase.

## Ejemplo ejecutable

`mongodb.php` es el archivo de ejemplo completo. Carga `config.php`, instancia `MongoDBCrud` y ejecuta operaciones CRUD sobre la coleccion `usuarios`:

```bash
php mongodb.php
```

El ejemplo demuestra:

- `create()` para insertar un documento.
- `createMany()` para insertar varios documentos.
- `findAll()` y `findOne()` para consultar.
- `findById()` para consultar mediante `ObjectId`.
- `count()` para contar documentos.
- `update()` y `updateMany()` para actualizar.
- `delete()` para eliminar un documento.

## Administracion de bases de datos

La clase `MongoDBDatabaseManager` se utiliza para crear y eliminar bases de datos. Se mantiene separada de `MongoDBCrud` porque son responsabilidades diferentes.

```php
use App\MongoDBDatabaseManager;

$config = require __DIR__ . '/config.php';
$manager = new MongoDBDatabaseManager($config['mongodb_uri']);

// MongoDB crea la base de datos al crear su primera coleccion.
$manager->crearBaseDatos('otra_app', 'usuarios');

// Operacion destructiva: elimina la base de datos completa.
$manager->eliminarBaseDatos('otra_app');
```

`crearBaseDatos()` no utiliza una operacion `createDatabase`, porque MongoDB no la proporciona. Crea la primera coleccion y, como consecuencia, materializa la base de datos.

`eliminarBaseDatos()` ejecuta `dropDatabase` y borra todas las colecciones y documentos. Debe utilizarse con permisos controlados y nunca directamente con nombres recibidos del usuario.

## API de `MongoDBCrud`

### `create(array $data): string`

Inserta un documento y devuelve su identificador como texto.

```php
$id = $crud->create([
    'nombre' => 'Ana Garcia',
    'activo' => true,
]);
```

### `createMany(array $documents): array`

Inserta varios documentos y devuelve un array de identificadores como texto.

```php
$ids = $crud->createMany([
    ['nombre' => 'Ana'],
    ['nombre' => 'Luis'],
]);
```

### `findAll(array $filter = [], array $options = []): array`

Busca todos los documentos que coinciden con el filtro y convierte el cursor completo a un array.

```php
$usuarios = $crud->findAll(
    ['edad' => ['$gte' => 18]],
    ['sort' => ['edad' => -1], 'limit' => 10]
);
```

`$filter` y `$options` se pasan al metodo `find()` del driver oficial. Se pueden utilizar operadores como `$gt`, `$gte`, `$in` y `$and`.

### `findById(string $id): ?array`

Busca por `_id`. Si el texto no es un `ObjectId` valido o no existe el documento, devuelve `null`. Si existe, devuelve un array.

```php
$usuario = $crud->findById($id);
```

### `findOne(array $filter): ?array`

Devuelve el primer documento coincidente o `null`.

```php
$usuario = $crud->findOne(['email' => 'ana@ejemplo.com']);
```

### `update(string $id, array $data): int`

Actualiza un documento por `_id` utilizando `$set` y devuelve el numero de documentos modificados. Un ID invalido devuelve `0`.

```php
$modificados = $crud->update($id, ['edad' => 26]);
```

### `updateMany(array $filter, array $data): int`

Actualiza todos los documentos coincidentes utilizando `$set` y devuelve el numero de documentos modificados.

```php
$modificados = $crud->updateMany(
    ['activo' => false],
    ['activo' => true]
);
```

### `delete(string $id): int`

Elimina como maximo un documento por `_id` y devuelve `0` o `1`. Un ID invalido devuelve `0`.

### `deleteMany(array $filter): int`

Elimina todos los documentos que coinciden con el filtro. Un filtro vacio (`[]`) coincide con todos los documentos, por lo que debe evitarse salvo que se pretenda vaciar la coleccion.

### `count(array $filter = []): int`

Devuelve el numero de documentos que coinciden con el filtro.

### `crearColeccion(string $nombreColeccion, array $opciones = []): bool`

Crea una coleccion y pasa `$opciones` a `Database::createCollection()`.

```php
$crud->crearColeccion('auditoria', [
    'capped' => true,
    'size' => 1048576,
]);
```

### `eliminarColeccion(string $nombreColeccion): bool`

Elimina completamente una coleccion y todos sus documentos. Es una operacion destructiva.

```php
$crud->eliminarColeccion('auditoria');
```

## Errores y tipos BSON

Se recomienda envolver el uso de la clase en `try/catch`. El constructor y los metodos de gestion de colecciones lanzan `Exception` con informacion contextual; las operaciones del driver pueden lanzar sus propias excepciones.

Para guardar fechas BSON utiliza `UTCDateTime`:

```php
use MongoDB\BSON\UTCDateTime;

$crud->create([
    'creado_en' => new UTCDateTime(),
]);
```

## Limitaciones

- Cada instancia trabaja con una sola coleccion.
- `findAll()` carga todos los resultados en memoria mediante `toArray()`.
- Las actualizaciones solo soportan `$set`.
- Los metodos por ID solo aceptan `ObjectId` validos.
- La clase no incorpora validacion de esquema, indices, transacciones ni paginacion.

## Archivos

- `src/MongoDBCrud.php`: implementacion de la clase.
- `src/MongoDBDatabaseManager.php`: administracion de bases de datos.
- `config.php`: URI y nombre de la base de datos.
- `mongodb.php`: ejemplo ejecutable que usa `config.php` y `MongoDBCrud`.
- `composer.json`: dependencias y autoload PSR-4.
