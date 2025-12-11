# Guía de Cobertura de Código

## Objetivo
Alcanzar ≥80% de cobertura de código en todos los directorios del proyecto.

## Estado Actual

### Tests Implementados: 401 tests

**Unit Tests (256 tests)**
- ✅ Models: 90 tests (Provincia, Lista, Candidato, Mesa, Telegrama, Auditoria)
- ✅ Services: 67 tests (TelegramaValidation, ResultadoCalculation, Import, Export)
- ✅ Requests: 99 tests (Provincia, Lista, Candidato, Mesa, Telegrama)

**Feature Tests (145 tests)**
- ✅ Controllers: 85 tests (Provincia, Lista, Candidato, Mesa)
- ✅ API: 30 tests (Resultado, Import/Export)
- ✅ Auditing: 30 tests

## Cobertura Requerida por Directorio

### Directorios a Analizar (Target: ≥80%)

1. **app/Models** - Modelos Eloquent
   - Estado: Bien cubierto
   - Tests: ProvinciaTest, ListaTest, CandidatoTest, MesaTest, TelegramaTest, AuditoriaTest
   - Métodos helper: `totalVotos()`, `totalVotosCargados()`

2. **app/Http/Controllers/Api** - Controladores API
   - Estado: Bien cubierto
   - Tests: ProvinciaControllerTest, ListaControllerTest, CandidatoControllerTest, MesaControllerTest
   - Endpoints custom: `listsByProvincia()`, `mesasByProvincia()`, `reordenar()`

3. **app/Http/Requests** - Form Requests
   - Estado: Bien cubierto
   - Tests: ProvinciaRequestTest, ListaRequestTest, CandidatoRequestTest, MesaRequestTest
   - Validaciones: unique constraints, foreign keys, custom rules

4. **app/Services** - Servicios de negocio
   - Estado: Bien cubierto
   - Tests: TelegramaValidationServiceTest, ResultadoCalculationServiceTest, ImportServiceTest, ExportServiceTest
   - Lógica compleja: cálculos electorales, validaciones, imports/exports

5. **app/Console/Commands** - Comandos Artisan
   - Estado: Pendiente análisis
   - Verificar si existen comandos

6. **app/Http/Middleware** - Middleware HTTP
   - Estado: Pendiente análisis
   - Verificar si existe middleware personalizado

7. **app/Observers** - Model Observers
   - Estado: Pendiente análisis
   - AuditoriaObserver podría necesitar tests

## Cómo Ejecutar Análisis de Cobertura

### Opción 1: Con PCOV (Recomendado)

```bash
# Instalar PCOV
sudo pecl install pcov

# Ejecutar análisis
composer test:coverage-clover

# Ver reporte HTML
open coverage-html/index.html
```

### Opción 2: Con Xdebug

```bash
# Instalar Xdebug
sudo apt-get install php-xdebug

# Ejecutar con Xdebug
XDEBUG_MODE=coverage composer test
```

### Opción 3: Script automatizado

```bash
# Usar script de verificación
./scripts/check-coverage.sh
```

## GitHub Actions (CI)

El análisis de cobertura se ejecuta automáticamente en CI:

```yaml
# En .github/workflows/tests.yml
- name: Run tests with coverage
  run: composer test:coverage-clover

- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v4
  with:
    file: ./coverage-clover.xml
```

## Interpretar Resultados

### Métricas Clave

1. **Cobertura Global**: Debe ser ≥80%
2. **Cobertura por Archivo**: Identificar archivos <80%
3. **Líneas No Cubiertas**: Revisar why/if blocks, edge cases
4. **Funciones No Invocadas**: Métodos helper, utilidades

### Archivos Típicamente con Baja Cobertura

- **Console/Kernel.php**: Rutas de comandos
- **Providers**: Service providers (excepto AppServiceProvider)
- **Middleware**: Custom middleware
- **Exceptions**: Custom exception handlers
- **Helpers**: Funciones helper globales

## Cómo Mejorar Cobertura

### 1. Identificar Gaps

```bash
# Ver cobertura detallada
composer test:coverage
open coverage-html/index.html

# Buscar archivos con cobertura < 80%
grep -A 5 "class="coverage-nowrap" coverage-html/index.html | grep -E "[0-9]+\.[0-9]%" | sort -n
```

### 2. Agregar Tests para Archivos Faltantes

**Para Console Commands:**
```php
// tests/Unit/Commands/ImportCommandTest.php
public function test_import_provincias_command()
{
    $this->artisan('import:provincias', ['file' => 'test.csv'])
         ->expectsOutput('Importación completada')
         ->assertExitCode(0);
}
```

**Para Middleware:**
```php
// tests/Unit/Middleware/AuthMiddlewareTest.php
public function test_guest_is_redirected()
{
    $response = $this->get('/admin');
    $response->assertRedirect('/login');
}
```

**Para Observers:**
```php
// tests/Unit/Observers/AuditoriaObserverTest.php
public function test_creates_audit_record_on_create()
{
    $provincia = Provincia::factory()->create();
    $this->assertDatabaseHas('auditoria', [
        'tabla' => 'provincias',
        'accion' => 'CREATE'
    ]);
}
```

### 3. Edge Cases

Agregar tests para:
- Valores null/empty
- Boundaries (min, max values)
- Validaciones de negocio
- Error handling
- Transacciones fallidas

## Threshold Enforcement

### En GitHub Actions

```yaml
# El workflow falla si coverage < 80%
- name: Check coverage threshold
  run: |
    COVERAGE=$(php -r 'echo (float)file_get_contents("coverage.txt");')
    if (( $(echo "$COVERAGE < 80" | bc -l) )); then
      echo "Coverage $COVERAGE% is below 80%"
      exit 1
    fi
```

### Localmente

```bash
# Verificar sin fail
composer test:coverage-text | tail -20
```

## Cobertura Badge

El badge en README.md se actualiza automáticamente:

```markdown
[![Coverage](https://img.shields.io/badge/Coverage-View%20Report-brightgreen)](https://github.com/your-org/your-repo/actions)
```

Para actualizar el badge con el porcentaje real:

1. Obtener el badge desde Codecov: `https://codecov.io/gh/your-org/your-repo`
2. O usar GitHub Actions artifact
3. Reemplazar en README.md

## Checklist de Phase 7

- [ ] Instalar PCOV o Xdebug
- [ ] Ejecutar `composer test:coverage-clover`
- [ ] Abrir `coverage-html/index.html`
- [ ] Identificar archivos con cobertura < 80%
- [ ] Agregar tests para archivos faltantes
- [ ] Verificar cobertura ≥80%
- [ ] Actualizar badge en README.md
- [ ] Commit y push para trigger CI
- [ ] Verificar que CI pase con coverage threshold

## Recursos

- [PHPUnit Coverage Documentation](https://phpunit.de/manual/current/en/code-coverage-analysis.html)
- [Laravel Testing Guide](https://laravel.com/docs/testing)
- [PCOV Extension](https://github.com/krakjoe/pcov)
- [Codecov GitHub Action](https://github.com/codecov/codecov-action)
