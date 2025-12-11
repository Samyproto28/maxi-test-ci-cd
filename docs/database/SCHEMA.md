# Database Schema Documentation

## Sistema de Gestión Electoral Argentina 2025

### Base de Datos: `comicios_argentina_2025`

---

## Tablas del Sistema

### 1. PROVINCIAS

Almacena las provincias argentinas donde se realizan las elecciones.

**Estructura:**
```sql
CREATE TABLE provincias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX provincias_codigo_index (codigo),
    UNIQUE KEY provincias_codigo_unique (codigo),
    UNIQUE KEY provincias_nombre_unique (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `nombre`: Nombre completo de la provincia (ej: "Buenos Aires")
- `codigo`: Código corto de la provincia (ej: "BA", "CABA")
- `created_at`, `updated_at`: Timestamps de auditoría

**Constraints:**
- `nombre` debe ser único
- `codigo` debe ser único

**Índices:**
- PRIMARY KEY en `id`
- UNIQUE INDEX en `codigo`
- UNIQUE INDEX en `nombre`
- INDEX adicional en `codigo` para búsquedas

---

### 2. LISTAS

Representa las listas electorales que participan en las elecciones.

**Estructura:**
```sql
CREATE TABLE listas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    alianza VARCHAR(100) NULL,
    provincia_id BIGINT UNSIGNED NOT NULL,
    cargo ENUM('DIPUTADOS', 'SENADORES') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY listas_provincia_id_foreign (provincia_id)
        REFERENCES provincias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    UNIQUE KEY listas_nombre_provincia_id_cargo_unique (nombre, provincia_id, cargo),
    INDEX listas_provincia_id_cargo_index (provincia_id, cargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `nombre`: Nombre de la lista electoral
- `alianza`: Alianza política a la que pertenece (opcional)
- `provincia_id`: FK a provincias
- `cargo`: Tipo de elección (DIPUTADOS o SENADORES)
- `created_at`, `updated_at`: Timestamps de auditoría

**Constraints:**
- FK `provincia_id` → `provincias.id` (ON DELETE RESTRICT, ON UPDATE CASCADE)
- UNIQUE (nombre, provincia_id, cargo) - Una lista con el mismo nombre no puede repetirse en la misma provincia para el mismo cargo

**Índices:**
- PRIMARY KEY en `id`
- UNIQUE COMPOUND INDEX en (nombre, provincia_id, cargo)
- COMPOUND INDEX en (provincia_id, cargo)

---

### 3. CANDIDATOS

Almacena los candidatos de cada lista electoral.

**Estructura:**
```sql
CREATE TABLE candidatos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    lista_id BIGINT UNSIGNED NOT NULL,
    provincia_id BIGINT UNSIGNED NOT NULL,
    cargo ENUM('DIPUTADOS', 'SENADORES') NOT NULL,
    orden INT UNSIGNED NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY candidatos_lista_id_foreign (lista_id)
        REFERENCES listas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY candidatos_provincia_id_foreign (provincia_id)
        REFERENCES provincias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    UNIQUE KEY candidatos_lista_id_orden_unique (lista_id, orden),
    INDEX candidatos_lista_id_orden_index (lista_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `nombre`: Nombre completo del candidato
- `lista_id`: FK a listas
- `provincia_id`: FK a provincias
- `cargo`: Cargo al que se postula
- `orden`: Posición en la lista (1 = cabeza de lista)
- `observaciones`: Notas adicionales sobre el candidato
- `created_at`, `updated_at`: Timestamps de auditoría

**Constraints:**
- FK `lista_id` → `listas.id` (ON DELETE CASCADE, ON UPDATE CASCADE)
  - Al eliminar una lista, se eliminan todos sus candidatos
- FK `provincia_id` → `provincias.id` (ON DELETE RESTRICT, ON UPDATE CASCADE)
- UNIQUE (lista_id, orden) - El orden debe ser único dentro de cada lista

**Índices:**
- PRIMARY KEY en `id`
- UNIQUE COMPOUND INDEX en (lista_id, orden)
- INDEX en provincia_id (automático por FK)

---

### 4. MESAS

Representa las mesas electorales donde se emiten los votos.

**Estructura:**
```sql
CREATE TABLE mesas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_mesa VARCHAR(20) NOT NULL UNIQUE,
    provincia_id BIGINT UNSIGNED NOT NULL,
    circuito VARCHAR(50) NULL,
    establecimiento VARCHAR(200) NULL,
    electores INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY mesas_provincia_id_foreign (provincia_id)
        REFERENCES provincias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    UNIQUE KEY mesas_id_mesa_unique (id_mesa),
    INDEX mesas_id_mesa_index (id_mesa),
    INDEX mesas_provincia_id_circuito_index (provincia_id, circuito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `id_mesa`: Código único de mesa electoral (ej: "MESA001")
- `provincia_id`: FK a provincias
- `circuito`: Circuito electoral (opcional)
- `establecimiento`: Nombre del establecimiento donde está la mesa (opcional)
- `electores`: Número de electores habilitados en la mesa
- `created_at`, `updated_at`: Timestamps de auditoría

**Constraints:**
- FK `provincia_id` → `provincias.id` (ON DELETE RESTRICT, ON UPDATE CASCADE)
- `id_mesa` debe ser único

**Índices:**
- PRIMARY KEY en `id`
- UNIQUE INDEX en `id_mesa`
- INDEX adicional en `id_mesa` para búsquedas
- COMPOUND INDEX en (provincia_id, circuito)

---

### 5. TELEGRAMAS

Almacena los resultados electorales (votos) de cada mesa para cada lista.

**Estructura:**
```sql
CREATE TABLE telegramas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mesa_id BIGINT UNSIGNED NOT NULL,
    lista_id BIGINT UNSIGNED NOT NULL,
    votos_diputados INT UNSIGNED DEFAULT 0 NOT NULL,
    votos_senadores INT UNSIGNED DEFAULT 0 NOT NULL,
    blancos INT UNSIGNED DEFAULT 0 NOT NULL,
    nulos INT UNSIGNED DEFAULT 0 NOT NULL,
    recurridos INT UNSIGNED DEFAULT 0 NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY telegramas_mesa_id_foreign (mesa_id)
        REFERENCES mesas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY telegramas_lista_id_foreign (lista_id)
        REFERENCES listas(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    UNIQUE KEY telegramas_mesa_id_lista_id_unique (mesa_id, lista_id),
    INDEX telegramas_mesa_id_lista_id_index (mesa_id, lista_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `mesa_id`: FK a mesas
- `lista_id`: FK a listas
- `votos_diputados`: Cantidad de votos para diputados
- `votos_senadores`: Cantidad de votos para senadores
- `blancos`: Votos en blanco
- `nulos`: Votos nulos
- `recurridos`: Votos recurridos/impugnados
- `usuario`: Usuario que cargó el telegrama
- `created_at`, `updated_at`: Timestamps de auditoría

**Constraints:**
- FK `mesa_id` → `mesas.id` (ON DELETE CASCADE, ON UPDATE CASCADE)
  - Al eliminar una mesa, se eliminan todos sus telegramas
- FK `lista_id` → `listas.id` (ON DELETE RESTRICT, ON UPDATE CASCADE)
- UNIQUE (mesa_id, lista_id) - Solo puede haber un telegrama por mesa y lista

**Índices:**
- PRIMARY KEY en `id`
- UNIQUE COMPOUND INDEX en (mesa_id, lista_id)
- INDEX en lista_id (automático por FK)

---

### 6. AUDITORIA

Registra todas las operaciones de creación, actualización y eliminación para auditoría.

**Estructura:**
```sql
CREATE TABLE auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    registro_id BIGINT UNSIGNED NOT NULL,
    accion ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
    datos_anteriores LONGTEXT NULL CHECK (json_valid(datos_anteriores)),
    datos_nuevos LONGTEXT NULL CHECK (json_valid(datos_nuevos)),
    usuario VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX auditoria_tabla_index (tabla),
    INDEX auditoria_registro_id_index (registro_id),
    INDEX auditoria_created_at_index (created_at),
    INDEX auditoria_tabla_registro_id_index (tabla, registro_id),
    INDEX auditoria_usuario_created_at_index (usuario, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columnas:**
- `id`: Identificador único autoincremental
- `tabla`: Nombre de la tabla afectada
- `registro_id`: ID del registro afectado
- `accion`: Tipo de operación (CREATE, UPDATE, DELETE)
- `datos_anteriores`: Estado anterior del registro en JSON (para UPDATE/DELETE)
- `datos_nuevos`: Estado nuevo del registro en JSON (para CREATE/UPDATE)
- `usuario`: Usuario que realizó la operación
- `created_at`: Timestamp de la operación (sin updated_at)

**Constraints:**
- `datos_anteriores` y `datos_nuevos` deben ser JSON válido

**Índices:**
- PRIMARY KEY en `id`
- INDEX en `tabla`
- INDEX en `registro_id`
- INDEX en `created_at`
- COMPOUND INDEX en (tabla, registro_id)
- COMPOUND INDEX en (usuario, created_at)

---

## Diagrama de Dependencias

```
PROVINCIAS
    ↓ (RESTRICT)
    ├─→ LISTAS
    │       ↓ (CASCADE)
    │       └─→ CANDIDATOS
    │       ↓ (RESTRICT)
    │       └─→ TELEGRAMAS
    │
    ├─→ CANDIDATOS (RESTRICT)
    │
    └─→ MESAS (RESTRICT)
            ↓ (CASCADE)
            └─→ TELEGRAMAS
```

## Reglas de Integridad Referencial

### ON DELETE RESTRICT
**Provincias no pueden eliminarse si tienen:**
- Listas asociadas
- Candidatos asociados
- Mesas asociadas

**Listas no pueden eliminarse si tienen:**
- Telegramas asociados

### ON DELETE CASCADE
**Al eliminar una Lista:**
- Se eliminan automáticamente todos sus Candidatos

**Al eliminar una Mesa:**
- Se eliminan automáticamente todos sus Telegramas

### ON UPDATE CASCADE
**Todos los cambios de ID se propagan automáticamente a las tablas relacionadas**

---

## Estrategia de Testing

### Tests de Integridad
1. Intentar insertar listas con provincia_id inexistente → Debe fallar
2. Intentar eliminar provincia con listas → Debe fallar (RESTRICT)
3. Eliminar lista con candidatos → Candidatos deben eliminarse (CASCADE)
4. Eliminar mesa con telegramas → Telegramas deben eliminarse (CASCADE)
5. Intentar eliminar lista con telegramas → Debe fallar (RESTRICT)

### Tests de Constraints Únicos
1. Insertar provincia con código duplicado → Debe fallar
2. Insertar lista duplicada (mismo nombre, provincia, cargo) → Debe fallar
3. Insertar candidato con orden duplicado en misma lista → Debe fallar
4. Insertar mesa con id_mesa duplicado → Debe fallar
5. Insertar telegrama duplicado (misma mesa y lista) → Debe fallar

### Tests de Índices
1. Verificar uso de índice en búsqueda por provincias.codigo
2. Verificar uso de índice compuesto en listas (provincia_id, cargo)
3. Verificar uso de índice único en telegramas (mesa_id, lista_id)
4. Verificar uso de índice temporal en auditoria.created_at

---

## Comandos de Mantenimiento

### Verificar Integridad
```sql
-- Verificar foreign keys
SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'comicios_argentina_2025'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Verificar índices
SHOW INDEX FROM telegramas;

-- Verificar constraints únicos
SELECT * FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'comicios_argentina_2025'
AND CONSTRAINT_TYPE = 'UNIQUE';
```

### Análisis de Rendimiento
```sql
-- Analizar query plan
EXPLAIN SELECT t.*, l.nombre FROM telegramas t
JOIN listas l ON t.lista_id = l.id
WHERE t.mesa_id = 1;

-- Estadísticas de tabla
SHOW TABLE STATUS FROM comicios_argentina_2025;

-- Fragmentación de índices
ANALYZE TABLE telegramas;
```

### Backup y Restore
```bash
# Backup completo
mysqldump -u root comicios_argentina_2025 > backup_$(date +%Y%m%d).sql

# Backup solo estructura
mysqldump -u root --no-data comicios_argentina_2025 > schema_only.sql

# Restore
mysql -u root comicios_argentina_2025 < backup.sql
```

---

## Notas de Implementación

1. **Charset y Collation**: Todas las tablas usan `utf8mb4_unicode_ci` para soportar caracteres especiales y acentos del español.

2. **Engine**: InnoDB se usa para todas las tablas para garantizar soporte de transacciones y foreign keys.

3. **Auto-increment**: Todas las PKs usan BIGINT UNSIGNED para soportar hasta 18 quintillones de registros.

4. **Timestamps**: Laravel gestiona automáticamente `created_at` y `updated_at` excepto en la tabla `auditoria` que solo usa `created_at`.

5. **ENUM vs VARCHAR**: Se usa ENUM para campos con valores limitados (cargo, accion) para optimización y validación a nivel de BD.

6. **JSON**: Los campos de auditoría usan LONGTEXT con validación JSON para flexibilidad en el almacenamiento de datos.

7. **Índices Compuestos**: Los índices compuestos están ordenados para maximizar su uso en las queries más comunes del sistema.
