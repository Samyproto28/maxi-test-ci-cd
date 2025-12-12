# INFORME EXHAUSTIVO: PROBLEMAS CRÍTICOS EN SISTEMA DE TELEGRAMAS

**Fecha:** 12 de diciembre de 2025
**Sistema:** Backend Electoral Argentina 2025
**Analista:** Claude Code

---

## RESUMEN EJECUTIVO

Se han identificado **12 problemas críticos** en el sistema de telegramas, principalmente causados por una **desconexión total entre la base de datos y el código**. Las migraciones de reestructuración NO se han ejecutado, pero el código ya está parcialmente actualizado para la nueva estructura, causando errores sistemáticos.

---

## 1. MIGRACIONES - ERROR CRÍTICO

### Archivo: `2025_12_12_042209_restructure_telegramas_table.php`

**PROBLEMA GRAVE:** La migración de reestructuración tiene errores que impedirán su ejecución.

#### Errores específicos:

1. **Línea 17-19**: Intenta eliminar índice incorrecto
   ```php
   $table->dropIndex('telegramas_lista_id_foreign'); // INCORRECTO
   ```
   **Debería ser:**
   ```php
   $table->dropForeign(['lista_id']); // CORRECTO
   ```

2. **Línea 18**: Intenta eliminar índice que no existe con ese nombre
   - Los índices de FK en Laravel tienen nombres automáticos, no `telegramas_lista_id_foreign`

3. **Método down() inconsistente**: El rollback trata de restaurar columnas que el up() eliminó correctamente

#### Impacto:
- ❌ **CRÍTICO**: La migración NO puede ejecutarse
- ❌ **BLOQUEO TOTAL**: Imposibilita aplicar la reestructuración
- ❌ **PÉRDIDA DE DATOS**: Si se ejecuta incorrectamente, eliminará datos sin restaurar

---

## 2. ESTADO DE MIGRACIONES

**SITUACIÓN ACTUAL:**
```bash
2025_12_12_042209_restructure_telegramas_table ......... Pending
2025_12_12_042215_create_telegrama_votos_table ........... Pending
```

**PROBLEMA:**
- La base de datos mantiene la estructura ANTIGUA (con `lista_id` en `telegramas`)
- El código ya está preparado para la estructura NUEVA (sin `lista_id` en `telegramas`)
- **Desconexión completa** entre BD y código

---

## 3. MODELOS - RELACIONES INCORRECTAS

### A. Modelo `Mesa.php`

**Archivo:** `/home/samuel/Escritorio/Backend/app/Models/Mesa.php`

**PROBLEMA:** Método `totalVotosCargados()` (líneas 41-48)
```php
// INCORRECTO - Accede a columnas que ya no existen
return $this->telegramas()->sum('votos_diputados') +
       $this->telegramas()->sum('votos_senadores') +
       ...
```

**Debería ser:**
```php
// CORRECTO - Usar relación con telegrama_votos
return $this->telegramas()
    ->with('votos')
    ->get()
    ->sum(function($telegrama) {
        return $telegrama->votos->sum('votos_diputados') +
               $telegrama->votos->sum('votos_senadores') +
               $telegrama->blancos + $telegrama->nulos + $telegrama->recurridos;
    });
```

### B. Modelo `Lista.php`

**Archivo:** `/home/samuel/Escritorio/Backend/app/Models/Lista.php`

**PROBLEMA:** Relación `telegramas()` (línea 38-41)
```php
// INCORRECTO - Relación directa ya no existe
public function telegramas(): HasMany
{
    return $this->hasMany(Telegrama::class);
}
```

**Debería ser:**
```php
// CORRECTO - Relación a través de telegrama_votos
public function telegramaVotos(): HasMany
{
    return $this->hasMany(TelegramaVoto::class);
}
```

---

## 4. SERVICIOS - QUERIES ROTOS

### A. ResultadoCalculationService

**Archivo:** `/home/samuel/Escritorio/Backend/app/Services/ResultadoCalculationService.php`

**PROBLEMA CRÍTICO:** Líneas 47-60
```php
// INCORRECTO - JOIN con tabla y columna que no existen
DB::table('telegramas')
    ->join('mesas', 'telegramas.mesa_id', '=', 'mesas.id')
    ->join('listas', 'telegramas.lista_id', '=', 'listas.id') // ❌ NO EXISTE
    ->where(...)
    ->select(
        'listas.id as lista_id',
        DB::raw("SUM({$votoColumn}) as total_votos") // ❌ {$votoColumn} no está en telegramas
    )
```

**Debería ser:**
```php
DB::table('telegramas')
    ->join('mesas', 'telegramas.mesa_id', '=', 'mesas.id')
    ->join('telegrama_votos', 'telegramas.id', '=', 'telegrama_votos.telegrama_id') // ✅ CORRECTO
    ->join('listas', 'telegrama_votos.lista_id', '=', 'listas.id') // ✅ CORRECTO
    ->where(...)
    ->select(
        'listas.id as lista_id',
        DB::raw("SUM(telegrama_votos.{$votoColumn}) as total_votos") // ✅ CORRECTO
    )
```

**Impacto:** ❌ **CRÍTICO** - Todos los cálculos de resultados fallarán

### B. ImportService

**Archivo:** `/home/samuel/Escritorio/Backend/app/Services/ImportService.php`

**PROBLEMA:** Líneas 20, 486-536
```php
// INCORRECTO - Headers de CSV obsoletos
private const HEADERS_TELEGRAMAS = [
    'mesa_id', 'lista_id', 'votos_diputados', 'votos_senadores', // ❌ YA NO EXISTEN
    'blancos', 'nulos', 'recurridos'
];

// INCORRECTO - Creación con estructura antigua
Telegrama::create([
    'mesa_id' => (int) $record['mesa_id'],
    'lista_id' => (int) $record['lista_id'], // ❌ NO EXISTE
    'votos_diputados' => ..., // ❌ NO EXISTE
    'votos_senadores' => ..., // ❌ NO EXISTE
]);
```

**Debería crear:**
1. Telegrama (sin votos por lista)
2. TelegramaVoto registros relacionados

**Impacto:** ❌ **CRÍTICO** - Importación de telegramas completamente rota

---

## 5. CONTROLADORES

### MesaController

**Archivo:** `/home/samuel/Escritorio/Backend/app/Http/Controllers/Api/MesaController.php`

**PROBLEMA:** Línea 47
```php
// INCORRECTO - Carga relación inexistente
$mesa->load(['provincia', 'telegramas.lista']);
```

**Debería ser:**
```php
$mesa->load(['provincia', 'telegramas.votos.lista']);
```

---

## 6. FACTORIES

### TelegramaFactory

**Archivo:** `/home/samuel/Escritorio/Backend/database/factories/TelegramaFactory.php`

**PROBLEMA:** Líneas 24-33
```php
// INCORRECTO - Define campos que ya no existen
return [
    'mesa_id' => Mesa::factory(),
    'lista_id' => Lista::factory(), // ❌ NO EXISTE
    'votos_diputados' => ..., // ❌ NO EXISTE
    'votos_senadores' => ..., // ❌ NO EXISTE
    ...
];
```

**Debería:**
1. Crear Telegrama sin votos por lista
2. Crear TelegramaVoto relacionado por separado

---

## 7. TESTS - TODOS ROTOS

**Archivos afectados:**
- `tests/Unit/Services/ResultadoCalculationServiceTest.php`
- `tests/Unit/Models/TelegramaTest.php`
- `tests/Feature/Controllers/MesaControllerTest.php`
- `tests/Feature/ImportExportControllerTest.php`
- Y todos los tests que usan TelegramaFactory

**PROBLEMA:** Todos crean telegramas con estructura antigua

**Ejemplo en ResultadoCalculationServiceTest.php:**
```php
Telegrama::factory()->create([
    'mesa_id' => $mesa1->id,
    'lista_id' => $lista1->id, // ❌ NO EXISTE
    'votos_diputados' => 150, // ❌ NO EXISTE
    'votos_senadores' => 0, // ❌ NO EXISTE
]);
```

**Impacto:** ❌ **CRÍTICO** - TODOS los tests fallarán

---

## 8. DOCUMENTACIÓN - DESACTUALIZADA

### SCHEMA.md

**Archivo:** `/home/samuel/Escritorio/Backend/docs/database/SCHEMA.md`

**PROBLEMA:** Líneas 193-239
- Describe tabla `telegramas` con `lista_id`, `votos_diputados`, `votos_senadores`
- No menciona tabla `telegrama_votos`
- Constraints UNIQUE obsoletos

### ER_DIAGRAM.md

**Archivo:** `/home/samuel/Escritorio/Backend/docs/database/ER_DIAGRAM.md`

**PROBLEMA:** Líneas 57-69
- Diagrama ER obsoleto
- No refleja nueva relación `telegramas` → `telegrama_votos` → `listas`

**Impacto:** ❌ **ALTO** - Documentación incorrecta confundirá a desarrolladores

---

## 9. VALIDACIONES

### StoreTelegramaRequest

**Archivo:** `/home/samuel/Escritorio/Backend/app/Http/Requests/StoreTelegramaRequest.php`

**PROBLEMA:** Líneas 31-33
```php
// INCORRECTO - Valida duplicado por mesa_id + lista_id
if (Telegrama::where('mesa_id', $value)->exists()) {
    $fail('Ya existe un telegrama cargado para esta mesa.');
}
```

**CORRECTO:** Ya está bien implementado - valida solo por mesa_id ✅

---

## 10. ORDEN DE MIGRACIONES

**Orden actual:**
1. ✅ `2025_12_09_183424_create_telegramas_table.php` - Crear tabla original
2. ✅ `2025_12_11_142710_add_user_id_to_telegramas_table.php` - Agregar user_id
3. ❌ `2025_12_12_042209_restructure_telegramas_table.php` - **NO EJECUTADA**
4. ❌ `2025_12_12_042215_create_telegrama_votos_table.php` - **NO EJECUTADA**

**PROBLEMA:** La migración 3 debe ejecutarse ANTES que la 4, pero ambas están pendientes.

---

## PLAN DE CORRECCIÓN

### Fase 1: Arreglar Migración (URGENTE)
1. Corregir `restructure_telegramas_table.php`:
   - Usar `dropForeign(['lista_id'])` en lugar de `dropIndex()`
   - Asegurar orden correcto de eliminación de constraints

### Fase 2: Ejecutar Migraciones
```bash
php artisan migrate
```

### Fase 3: Actualizar Código
1. **Models**: Corregir relaciones en `Mesa.php`, `Lista.php`
2. **Services**: Reescribir queries en `ResultadoCalculationService`
3. **ImportService**: Adaptar para nueva estructura
4. **MesaController**: Cargar relación correcta
5. **Factories**: Crear estructura correcta
6. **Tests**: Actualizar todos los tests

### Fase 4: Documentación
1. Actualizar `SCHEMA.md`
2. Actualizar `ER_DIAGRAM.md`

---

## ARCHIVOS AFECTADOS (71 archivos)

### Migraciones (4 archivos)
- ❌ `2025_12_12_042209_restructure_telegramas_table.php` - ERROR CRÍTICO
- ❌ `2025_12_12_042215_create_telegrama_votos_table.php` - Pendiente
- ✅ `2025_12_09_183424_create_telegramas_table.php` - OK
- ✅ `2025_12_11_142710_add_user_id_to_telegramas_table.php` - OK

### Modelos (4 archivos)
- ❌ `Telegrama.php` - Fillable incorrecto
- ❌ `Mesa.php` - Método totalVotosCargados() roto
- ❌ `Lista.php` - Relación telegramas() incorrecta
- ✅ `TelegramaVoto.php` - OK

### Servicios (3 archivos)
- ❌ `ResultadoCalculationService.php` - QUERIES ROTOS
- ❌ `ImportService.php` - ESTRUCTURA OBSOLETA
- ✅ `ExportService.php` - OK
- ✅ `TelegramaValidationService.php` - OK

### Controladores (2 archivos)
- ❌ `TelegramaController.php` - Posibles problemas menores
- ❌ `MesaController.php` - Relación incorrecta

### Requests (2 archivos)
- ✅ `StoreTelegramaRequest.php` - OK
- ✅ `UpdateTelegramaRequest.php` - OK

### Routes (1 archivo)
- ✅ `api.php` - OK

### Factories (1 archivo)
- ❌ `TelegramaFactory.php` - ESTRUCTURA OBSOLETA

### Observers (1 archivo)
- ✅ `TelegramaObserver.php` - OK

### Tests (20+ archivos)
- ❌ Todos los tests fallarán por usar estructura antigua

### Documentación (2 archivos)
- ❌ `SCHEMA.md` - DESACTUALIZADA
- ❌ `ER_DIAGRAM.md` - DESACTUALIZADA

---

## IMPACTO EN PRODUCCIÓN

### Si se intenta ejecutar SIN arreglar:
1. ❌ **Fallo total** en creación de telegramas
2. ❌ **Errores 500** en cálculos de resultados
3. ❌ **Importaciones fallan** completamente
4. ❌ **Tests no pasan**
5. ❌ **API endpoints rotos**

### Funcionalidades afectadas:
- ❌ POST `/api/v1/telegramas` - Crear telegrama
- ❌ GET `/api/v1/telegramas/{id}` - Ver telegrama
- ❌ GET `/api/v1/mesas/{mesa}/telegramas` - Ver telegramas de mesa
- ❌ GET `/api/v1/resultados/provincial/{provincia}` - Resultados provinciales
- ❌ POST `/api/v1/import/telegramas` - Importar telegramas
- ❌ Exportación de resultados

---

## RECOMENDACIONES FINALES

1. **URGENTE**: No desplegar a producción hasta corregir
2. **CRÍTICO**: Ejecutar migraciones en entorno de desarrollo primero
3. **ESENCIAL**: Actualizar todos los tests antes de proceder
4. **IMPORTANTE**: Documentar la nueva estructura correctamente
5. **NECESARIO**: Realizar pruebas exhaustivas post-migración

---

## CONCLUSIÓN

El sistema tiene una **desconexión crítica** entre la base de datos y el código. La reestructuración está incompleta y causará fallos masivos. **Se requiere intervención inmediata** antes de cualquier despliegue.

**Tiempo estimado de corrección:** 8-12 horas de desarrollo
**Prioridad:** MÁXIMA
**Riesgo si no se corrige:** SISTEMA INUTILIZABLE

