# PRD - Backend API
## Sistema de Carga y Conteo de Comicios Argentina 2025
### (Diputados y Senadores)

---

| Campo | Valor |
|-------|-------|
| **Versión del Documento** | 1.0 |
| **Fecha** | Diciembre 2025 |
| **Autores** | Ignacio González, Candela Ybañez Barrios, Silvina Torales, Samuel Angarita |
| **Estado** | En Desarrollo |
| **Stack Tecnológico** | Laravel 11 + PHP 8.2 + MySQL 8.0 |
| **Metodología** | TDD (Test-Driven Development) + Unit Testing |

---

## Índice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Declaración del Problema Técnico](#2-declaración-del-problema-técnico)
3. [Objetivos Técnicos](#3-objetivos-técnicos)
4. [Arquitectura del Sistema](#4-arquitectura-del-sistema)
5. [Modelo de Datos](#5-modelo-de-datos)
6. [Especificación de API REST](#6-especificación-de-api-rest)
7. [Requerimientos Técnicos](#7-requerimientos-técnicos)
8. [Estrategia de Testing (TDD)](#8-estrategia-de-testing-tdd)
9. [Plan de Implementación](#9-plan-de-implementación)
10. [Análisis de Riesgos](#10-análisis-de-riesgos)
11. [Dependencias](#11-dependencias)
12. [Métricas de Éxito](#12-métricas-de-éxito)
13. [Anexos](#13-anexos)

---

## 1. Resumen Ejecutivo

### 1.1 Visión General

Este documento define los requerimientos técnicos para el desarrollo del Backend (REST API) del Sistema de Carga y Conteo de Comicios Argentina 2025. El sistema permitirá gestionar la información electoral de los comicios legislativos, incluyendo la carga de telegramas de mesa, validación de datos, y generación de reportes de resultados para Diputados y Senadores a nivel provincial y nacional.

### 1.2 Impacto Técnico

- API RESTful completa para operaciones CRUD de entidades electorales
- Sistema de validación robusto para integridad de datos electorales
- Importación masiva de datos desde archivos CSV/JSON
- Exportación de resultados en múltiples formatos
- Sistema de auditoría con registro de fecha/hora y usuario

### 1.3 Valor de Negocio

El backend proveerá una infraestructura confiable y escalable que permitirá a los operadores del centro de cómputos cargar telegramas electorales de manera eficiente, minimizando errores humanos y garantizando la transparencia del proceso de escrutinio mediante validaciones automáticas y registro de auditoría.

### 1.4 Fecha Objetivo

Finalización estimada: 20 horas de desarrollo distribuidas en 4 semanas.

---

## 2. Declaración del Problema Técnico

### 2.1 Contexto del Problema

El proceso de carga y conteo de votos electorales requiere un sistema backend robusto que pueda manejar múltiples operaciones concurrentes, validar la integridad de los datos en tiempo real, y mantener un registro auditable de todas las operaciones realizadas. Actualmente, no existe una solución automatizada que cumpla con estos requisitos para el contexto académico del proyecto.

### 2.2 Análisis de Causa Raíz

- Necesidad de validaciones complejas: suma de votos ≤ electores, consistencia de totales
- Requerimiento de trazabilidad completa de operaciones
- Necesidad de importación/exportación masiva de datos
- Generación de estadísticas agregadas en múltiples niveles (mesa, provincia, nacional)

### 2.3 Impacto de No Abordar el Problema

Sin un backend adecuado, el sistema no podría garantizar la integridad de los datos electorales, lo que comprometería la confiabilidad del proceso de escrutinio. Los errores humanos en la carga manual podrían propagarse sin detección, afectando los resultados finales.

---

## 3. Objetivos Técnicos

### 3.1 Objetivos Primarios

1. Implementar una API RESTful completa con Laravel 11 siguiendo las mejores prácticas
2. Diseñar un modelo de datos relacional normalizado en MySQL para entidades electorales
3. Desarrollar sistema de validación de datos con reglas de negocio electorales
4. Implementar importación/exportación de datos en formatos CSV y JSON
5. Crear sistema de auditoría con registro de fecha/hora y usuario
6. Alcanzar cobertura de tests unitarios ≥ 80% mediante TDD

### 3.2 No-Objetivos (Fuera de Alcance)

- Autenticación avanzada con OAuth2 o JWT (se usará autenticación básica)
- Procesamiento de imágenes de telegramas
- Integración con sistemas electorales oficiales
- Cálculo de bancas por sistema D'Hondt (extensión opcional)
- Roles y permisos avanzados

---

## 4. Arquitectura del Sistema

### 4.1 Arquitectura Propuesta

El backend seguirá una arquitectura en capas (Layered Architecture) utilizando el patrón MVC de Laravel, con separación clara de responsabilidades:

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                     │
│                    (API REST Controllers)                   │
├─────────────────────────────────────────────────────────────┤
│                    CAPA DE APLICACIÓN                       │
│              (Services / Business Logic)                    │
├─────────────────────────────────────────────────────────────┤
│                    CAPA DE DOMINIO                          │
│           (Models / Eloquent / Validations)                 │
├─────────────────────────────────────────────────────────────┤
│                    CAPA DE DATOS                            │
│              (MySQL / Repositories)                         │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 Componentes Principales

#### 4.2.1 Controllers (Capa de Presentación)

- **ProvinciaController**: ABM de provincias
- **ListaController**: Gestión de listas y alianzas
- **CandidatoController**: ABM de candidatos
- **MesaController**: Gestión de mesas electorales
- **TelegramaController**: Carga y validación de telegramas
- **ResultadoController**: Consultas y reportes
- **ImportExportController**: Importación/exportación CSV/JSON

#### 4.2.2 Services (Capa de Aplicación)

- **TelegramaValidationService**: Validación de reglas de negocio
- **ResultadoCalculationService**: Cálculo de porcentajes y totales
- **ImportService**: Procesamiento de archivos CSV/JSON
- **ExportService**: Generación de reportes exportables
- **AuditService**: Registro de auditoría

---

## 5. Modelo de Datos

### 5.1 Diagrama Entidad-Relación

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│  PROVINCIAS  │       │    LISTAS    │       │  CANDIDATOS  │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id           │◄──┐   │ id           │◄──┐   │ id           │
│ nombre       │   │   │ nombre       │   │   │ nombre       │
│ codigo       │   │   │ alianza      │   │   │ cargo        │
│ created_at   │   │   │ provincia_id │───┘   │ lista_id     │───┐
│ updated_at   │   │   │ cargo        │       │ provincia_id │───┤
└──────────────┘   │   │ created_at   │       │ orden        │   │
                   │   │ updated_at   │       │ observaciones│   │
                   │   └──────────────┘       │ created_at   │   │
                   │                          │ updated_at   │   │
                   │                          └──────────────┘   │
                   │                                             │
┌──────────────┐   │   ┌──────────────────┐                     │
│    MESAS     │   │   │    TELEGRAMAS    │                     │
├──────────────┤   │   ├──────────────────┤                     │
│ id           │◄──┼───│ id               │                     │
│ id_mesa      │   │   │ mesa_id          │                     │
│ provincia_id │───┘   │ lista_id         │─────────────────────┘
│ circuito     │       │ votos_diputados  │
│ establecim.  │       │ votos_senadores  │
│ electores    │       │ blancos          │
│ created_at   │       │ nulos            │
│ updated_at   │       │ recurridos       │
└──────────────┘       │ usuario          │
                       │ created_at       │
                       │ updated_at       │
                       └──────────────────┘
```

### 5.2 Definición de Tablas

#### 5.2.1 Tabla: `provincias`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| nombre | VARCHAR(100) | NO | Nombre de la provincia |
| codigo | VARCHAR(10) | NO | Código único de provincia |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de actualización |

#### 5.2.2 Tabla: `listas`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| nombre | VARCHAR(100) | NO | Nombre de la lista |
| alianza | VARCHAR(100) | SÍ | Alianza/Frente político |
| provincia_id | BIGINT | NO | FK a provincias.id |
| cargo | ENUM | NO | DIPUTADOS \| SENADORES |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de actualización |

#### 5.2.3 Tabla: `candidatos`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| nombre | VARCHAR(150) | NO | Nombre completo del candidato |
| lista_id | BIGINT | NO | FK a listas.id |
| provincia_id | BIGINT | NO | FK a provincias.id |
| cargo | ENUM | NO | DIPUTADOS \| SENADORES |
| orden | INT | NO | Posición en la lista |
| observaciones | TEXT | SÍ | Notas adicionales |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de actualización |

#### 5.2.4 Tabla: `mesas`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| id_mesa | VARCHAR(20) | NO | Identificador único de mesa |
| provincia_id | BIGINT | NO | FK a provincias.id |
| circuito | VARCHAR(50) | SÍ | Circuito electoral |
| establecimiento | VARCHAR(200) | SÍ | Nombre de la escuela/lugar |
| electores | INT | NO | Cantidad de electores habilitados |
| created_at | TIMESTAMP | NO | Fecha de creación |
| updated_at | TIMESTAMP | NO | Fecha de actualización |

#### 5.2.5 Tabla: `telegramas`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| mesa_id | BIGINT | NO | FK a mesas.id |
| lista_id | BIGINT | NO | FK a listas.id |
| votos_diputados | INT | NO | Votos para diputados (≥0) |
| votos_senadores | INT | NO | Votos para senadores (≥0) |
| blancos | INT | NO | Votos en blanco |
| nulos | INT | NO | Votos nulos |
| recurridos | INT | NO | Votos recurridos/impugnados |
| usuario | VARCHAR(100) | NO | Usuario que realizó la carga |
| created_at | TIMESTAMP | NO | Fecha/hora de creación |
| updated_at | TIMESTAMP | NO | Fecha/hora de actualización |

#### 5.2.6 Tabla: `auditoria`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| id | BIGINT | NO | PK autoincremental |
| tabla | VARCHAR(50) | NO | Nombre de la tabla afectada |
| registro_id | BIGINT | NO | ID del registro modificado |
| accion | ENUM | NO | CREATE \| UPDATE \| DELETE |
| datos_anteriores | JSON | SÍ | Estado anterior del registro |
| datos_nuevos | JSON | SÍ | Estado nuevo del registro |
| usuario | VARCHAR(100) | NO | Usuario que realizó el cambio |
| created_at | TIMESTAMP | NO | Fecha/hora del cambio |

---

## 6. Especificación de API REST

### 6.1 Convenciones Generales

- **Base URL**: `/api/v1`
- **Formato de respuesta**: JSON
- **Códigos HTTP estándar**: 200 (OK), 201 (Created), 400 (Bad Request), 404 (Not Found), 422 (Validation Error), 500 (Server Error)
- **Paginación**: `?page=1&per_page=15`

### 6.2 Endpoints de Provincias

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/provincias` | Listar todas las provincias |
| GET | `/api/v1/provincias/{id}` | Obtener provincia por ID |
| POST | `/api/v1/provincias` | Crear nueva provincia |
| PUT | `/api/v1/provincias/{id}` | Actualizar provincia |
| DELETE | `/api/v1/provincias/{id}` | Eliminar provincia |

### 6.3 Endpoints de Listas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/listas` | Listar todas las listas |
| GET | `/api/v1/listas/{id}` | Obtener lista por ID |
| GET | `/api/v1/provincias/{id}/listas` | Listas por provincia |
| POST | `/api/v1/listas` | Crear nueva lista |
| PUT | `/api/v1/listas/{id}` | Actualizar lista |
| DELETE | `/api/v1/listas/{id}` | Eliminar lista |

### 6.4 Endpoints de Candidatos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/candidatos` | Listar todos los candidatos |
| GET | `/api/v1/candidatos/{id}` | Obtener candidato por ID |
| GET | `/api/v1/candidatos/{id}/resultados` | Resultados del candidato |
| POST | `/api/v1/candidatos` | Crear nuevo candidato |
| PUT | `/api/v1/candidatos/{id}` | Actualizar candidato |
| DELETE | `/api/v1/candidatos/{id}` | Eliminar candidato |

### 6.5 Endpoints de Mesas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/mesas` | Listar todas las mesas |
| GET | `/api/v1/mesas/{id}` | Obtener mesa por ID |
| GET | `/api/v1/provincias/{id}/mesas` | Mesas por provincia |
| POST | `/api/v1/mesas` | Crear nueva mesa |
| PUT | `/api/v1/mesas/{id}` | Actualizar mesa |
| DELETE | `/api/v1/mesas/{id}` | Eliminar mesa |

### 6.6 Endpoints de Telegramas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/telegramas` | Listar todos los telegramas |
| GET | `/api/v1/telegramas/{id}` | Obtener telegrama por ID |
| GET | `/api/v1/mesas/{id}/telegramas` | Telegramas por mesa |
| POST | `/api/v1/telegramas` | Crear nuevo telegrama (con validación) |
| PUT | `/api/v1/telegramas/{id}` | Actualizar telegrama (reemplazo) |
| DELETE | `/api/v1/telegramas/{id}` | Eliminar telegrama |

### 6.7 Endpoints de Resultados

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/resultados/provincial/{provincia_id}` | Resultados por provincia |
| GET | `/api/v1/resultados/nacional` | Resumen nacional |
| GET | `/api/v1/resultados/candidato/{candidato_id}` | Resultados de candidato |
| GET | `/api/v1/resultados/lista/{lista_id}` | Resultados de lista |

### 6.8 Endpoints de Importación/Exportación

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/import/provincias` | Importar provincias desde CSV |
| POST | `/api/v1/import/listas` | Importar listas desde CSV |
| POST | `/api/v1/import/candidatos` | Importar candidatos desde CSV |
| POST | `/api/v1/import/mesas` | Importar mesas desde CSV |
| POST | `/api/v1/import/telegramas` | Importar telegramas desde CSV |
| GET | `/api/v1/export/provincial/{id}` | Exportar resultados provinciales |
| GET | `/api/v1/export/nacional` | Exportar resumen nacional |

---

## 7. Requerimientos Técnicos

### 7.1 Requerimientos Funcionales

#### RF-001: Gestión de Provincias

El sistema **SHALL** permitir operaciones CRUD completas sobre la entidad Provincia.

**Criterio de Aceptación:**
- DADO un usuario autenticado, CUANDO envía una solicitud POST a `/api/v1/provincias` con datos válidos, ENTONCES el sistema crea la provincia y retorna código 201.

#### RF-002: Gestión de Listas y Candidatos

El sistema **SHALL** permitir registrar listas por provincia y por cargo (Diputados/Senadores), y asociar candidatos a cada lista.

**Criterio de Aceptación:**
- DADO una lista existente, CUANDO se crea un candidato asociado, ENTONCES el candidato aparece en la lista con su orden correcto.

#### RF-003: Carga de Telegramas

El sistema **SHALL** permitir registrar telegramas por mesa con votos por lista, votos en blanco, nulos, y recurridos.

**Criterio de Aceptación:**
- DADO un telegrama con suma de votos ≤ electores de la mesa, CUANDO se envía POST a `/api/v1/telegramas`, ENTONCES el sistema lo guarda exitosamente.

#### RF-004: Validación de Datos

El sistema **SHALL** validar que la suma de votos (listas + blancos + nulos + recurridos) no supere la cantidad de electores de la mesa.

**Criterio de Aceptación:**
- DADO un telegrama con suma de votos > electores, CUANDO se intenta guardar, ENTONCES el sistema retorna código 422 con mensaje de error.

#### RF-005: Importación CSV/JSON

El sistema **SHALL** permitir importar datos desde archivos CSV y JSON siguiendo los formatos especificados.

**Criterio de Aceptación:**
- DADO un archivo CSV con 8 mesas y 3 listas, CUANDO se importa, ENTONCES el sistema crea todos los registros y reporta inconsistencias encontradas.

#### RF-006: Generación de Resultados

El sistema **SHALL** calcular y retornar resultados agregados por candidato, lista, provincia y a nivel nacional.

**Criterio de Aceptación:**
- DADO telegramas cargados para una provincia, CUANDO se consulta `/api/v1/resultados/provincial/{id}`, ENTONCES retorna votos totales por lista, porcentajes y participación.

#### RF-007: Exportación de Resultados

El sistema **SHALL** permitir exportar resultados provinciales y nacionales en formato CSV.

**Criterio de Aceptación:**
- DADO resultados calculados, CUANDO se solicita exportación, ENTONCES el sistema genera un archivo CSV descargable con los datos.

#### RF-008: Registro de Auditoría

El sistema **SHALL** registrar fecha/hora y usuario de cada carga/edición de telegrama.

**Criterio de Aceptación:**
- DADO un telegrama modificado, CUANDO se consulta el historial, ENTONCES muestra todas las versiones con fecha, hora y usuario.

### 7.2 Requerimientos No Funcionales

#### RNF-001: Rendimiento

- **PERF-001**: Las consultas de API **SHALL** responder en menos de 500ms para el 95% de las solicitudes.
- **PERF-002**: La importación de un archivo CSV con 100 registros **SHALL** completarse en menos de 10 segundos.
- **PERF-003**: El cálculo de resultados nacionales **SHALL** completarse en menos de 3 segundos.

#### RNF-002: Confiabilidad

- **REL-001**: El sistema **SHALL** mantener integridad referencial en todas las relaciones de base de datos.
- **REL-002**: Las operaciones de escritura **SHALL** ser transaccionales para garantizar consistencia.
- **REL-003**: El sistema **SHALL** persistir datos a archivo/base local para recuperación.

#### RNF-003: Seguridad

- **SEC-001**: Todas las entradas de usuario **SHALL** ser validadas y sanitizadas.
- **SEC-002**: El sistema **SHALL** usar prepared statements para prevenir SQL injection.
- **SEC-003**: Los endpoints **SHALL** implementar validación de datos con Laravel Form Requests.

#### RNF-004: Mantenibilidad

- **MAINT-001**: El código **SHALL** seguir los estándares PSR-12 de PHP.
- **MAINT-002**: La cobertura de tests unitarios **SHALL** ser ≥ 80%.
- **MAINT-003**: Cada función pública **SHALL** tener documentación PHPDoc.

#### RNF-005: Usabilidad de API

- **USE-001**: Los mensajes de error **SHALL** ser descriptivos e incluir el campo con error.
- **USE-002**: Las respuestas de error **SHALL** seguir el formato estándar de Laravel.
- **USE-003**: La API **SHALL** incluir paginación en listados con más de 15 elementos.

---

## 8. Estrategia de Testing (TDD)

### 8.1 Enfoque TDD

El desarrollo seguirá el ciclo **Red-Green-Refactor** de TDD: primero se escriben los tests que fallan (Red), luego se implementa el código mínimo para que pasen (Green), y finalmente se refactoriza manteniendo los tests verdes (Refactor).

### 8.2 Tipos de Tests

#### 8.2.1 Tests Unitarios

- **TelegramaValidationServiceTest**: Validación de reglas de negocio de telegramas
- **ResultadoCalculationServiceTest**: Cálculo de porcentajes y totales
- **ImportServiceTest**: Procesamiento de archivos CSV/JSON
- **Model Tests**: Validación de atributos y relaciones de cada modelo

#### 8.2.2 Tests de Feature (API)

- **ProvinciaControllerTest**: CRUD completo de provincias
- **ListaControllerTest**: CRUD de listas con validaciones
- **CandidatoControllerTest**: CRUD de candidatos
- **MesaControllerTest**: CRUD de mesas
- **TelegramaControllerTest**: Carga y validación de telegramas
- **ResultadoControllerTest**: Consultas de resultados
- **ImportExportControllerTest**: Importación/exportación de datos

### 8.3 Casos de Test Críticos

| Caso de Test | Descripción |
|--------------|-------------|
| `test_telegrama_votos_no_exceden_electores` | Verifica que suma de votos ≤ electores de mesa |
| `test_telegrama_reemplaza_anterior` | Verifica que nuevo telegrama reemplaza versión anterior |
| `test_auditoria_registra_cambios` | Verifica que cada cambio queda registrado |
| `test_calculo_porcentaje_correcto` | Verifica cálculo correcto de % de votos |
| `test_import_csv_con_errores` | Verifica manejo de errores en importación |
| `test_resultado_provincial_suma` | Verifica agregación correcta por provincia |
| `test_resultado_nacional_ranking` | Verifica ranking nacional de listas |
| `test_export_csv_formato_valido` | Verifica formato correcto de exportación |

### 8.4 Métricas de Calidad

| Métrica | Objetivo | Herramienta |
|---------|----------|-------------|
| Cobertura de código | ≥ 80% | PHPUnit + Coverage |
| Tests pasando | 100% | PHPUnit |
| Complejidad ciclomática | ≤ 10 por método | PHP_CodeSniffer |
| Cumplimiento PSR-12 | 100% | Laravel Pint |

---

## 9. Plan de Implementación

### 9.1 Cronograma (20 horas totales)

#### Semana 1: Fundamentos (5 horas)

1. Configuración inicial del proyecto Laravel
2. Creación de migraciones de base de datos
3. Definición de modelos Eloquent con relaciones
4. CRUD básico de Provincias, Listas, Candidatos
5. Tests unitarios de modelos

#### Semana 2: Carga de Datos (5 horas)

1. CRUD de Mesas y Telegramas
2. Implementación de TelegramaValidationService
3. Sistema de importación CSV/JSON
4. Validaciones de datos electorales
5. Tests de feature para importación

#### Semana 3: Resultados y Exportación (5 horas)

1. Implementación de ResultadoCalculationService
2. Endpoints de consulta por candidato/lista/provincia/nacional
3. Sistema de exportación CSV
4. Sistema de auditoría
5. Tests de integración

#### Semana 4: Finalización (5 horas)

1. Pruebas finales y corrección de bugs
2. Documentación de API (README)
3. Generación de datos de prueba
4. Empaquetado y preparación de demo
5. Revisión de cobertura de tests

---

## 10. Análisis de Riesgos

### 10.1 Matriz de Riesgos Técnicos

| Riesgo | Impacto | Probabilidad | Mitigación |
|--------|---------|--------------|------------|
| Pérdida de código/commits | Alto | Media | Commits frecuentes, backups locales, repositorio Git |
| Sobrecarga de datos | Alto | Media | Pruebas de carga antes de presentación |
| Errores en conexión Frontend-Backend | Alto | Media | Tests de integración, documentación clara de API |
| Validaciones incorrectas | Crítico | Baja | TDD con casos de borde, revisión de código |
| Tiempo insuficiente | Alto | Media | Priorización MoSCoW, desarrollo incremental |

### 10.2 Estrategia de Rollback

**Criterios de Activación:**
- Falla crítica en validación de datos
- Corrupción de datos en base de datos
- Degradación severa de rendimiento (>5 segundos por request)

**Procedimiento de Rollback:**
1. Revertir a última versión estable del código (`git revert`)
2. Restaurar base de datos desde backup más reciente
3. Ejecutar suite de tests para validar estado
4. Comunicar al equipo y documentar incidente

---

## 11. Dependencias

### 11.1 Dependencias de Software

| Componente | Versión | Propósito |
|------------|---------|-----------|
| PHP | 8.2+ | Lenguaje de programación principal |
| Laravel | 11.x | Framework PHP para desarrollo de API REST |
| MySQL | 8.0+ | Sistema de gestión de base de datos relacional |
| Composer | 2.x | Gestor de dependencias PHP |
| PHPUnit | 10.x | Framework de testing |
| Laravel Sanctum | 4.x | Autenticación de API (opcional) |

### 11.2 Paquetes Laravel Recomendados

| Paquete | Uso |
|---------|-----|
| `spatie/laravel-query-builder` | Filtrado y ordenamiento en API endpoints |
| `league/csv` | Lectura y escritura de archivos CSV |
| `owen-it/laravel-auditing` | Sistema de auditoría automático |
| `laravel/pint` | Formateo de código PSR-12 |

### 11.3 Dependencias de Equipo

- Coordinación con equipo Frontend para definición de contratos de API
- Revisión de código entre integrantes del equipo
- Definición conjunta de datos de prueba

---

## 12. Métricas de Éxito

### 12.1 Métricas Técnicas

| Métrica | Objetivo | Medición |
|---------|----------|----------|
| Cobertura de tests | ≥ 80% | PHPUnit --coverage-html |
| Tiempo de respuesta API | < 500ms P95 | Laravel Telescope / Logs |
| Endpoints implementados | 100% | Checklist de API |
| Validaciones funcionando | 100% | Tests de validación |
| Cumplimiento PSR-12 | 100% | Laravel Pint |

### 12.2 Criterios de Aceptación del Proyecto

- ✅ Importación exitosa de CSV con 8 mesas y 3 listas en 2 provincias
- ✅ Reporta inconsistencias en datos importados
- ✅ Muestra resultados por candidato (votos y %) en su provincia
- ✅ Muestra tabla por lista y cargo con % y participación
- ✅ Genera resumen nacional con ranking por lista
- ✅ Exporta resultados en formato CSV

---

## 13. Anexos

### 13.1 Glosario

| Término | Definición |
|---------|------------|
| Telegrama | Documento que registra los votos de una mesa electoral |
| Mesa | Unidad de votación donde los electores emiten su voto |
| Lista | Agrupación de candidatos que compiten en una elección |
| Alianza/Frente | Coalición de partidos políticos |
| Electores | Cantidad de personas habilitadas para votar en una mesa |
| Votos válidos | Suma de votos a listas (excluye blancos, nulos, recurridos) |
| TDD | Test-Driven Development - Desarrollo guiado por pruebas |
| CRUD | Create, Read, Update, Delete - Operaciones básicas de datos |

### 13.2 Formatos de Archivo CSV

#### listas.csv
```csv
provincia,cargo,lista,alianza
Buenos Aires,DIPUTADOS,Lista A,Frente X
Buenos Aires,SENADORES,Lista A,Frente X
```

#### candidatos.csv
```csv
provincia,cargo,lista,nombre,orden_en_lista
Buenos Aires,DIPUTADOS,Lista A,Ana Pérez,1
Buenos Aires,DIPUTADOS,Lista A,Carlos Soria,2
```

#### mesas.csv
```csv
id_mesa,provincia,circuito,establecimiento,electores
1001,Buenos Aires,0101,Escuela 12,350
```

#### telegramas.csv
```csv
id_mesa,provincia,lista,votos_diputados,votos_senadores,blancos,nulos,recurridos
1001,Buenos Aires,Lista A,120,90,8,5,1
1001,Buenos Aires,Lista B,100,110,8,5,1
```

### 13.3 Historial de Revisiones

| Versión | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0 | Diciembre 2025 | Equipo ESET-UNQ | Documento inicial |

---

**Documento generado para el Taller de Análisis y Evaluación de Proyecto - ESET UNQ**