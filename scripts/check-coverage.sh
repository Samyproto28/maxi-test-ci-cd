#!/bin/bash

# Script para verificar cobertura de código
# Requiere PCOV o Xdebug instalado

echo "======================================"
echo "Verificación de Cobertura de Código"
echo "======================================"
echo ""

# Verificar si PCOV está disponible
if ! php -r "exit(extension_loaded('pcov') ? 0 : 1);"; then
    echo "⚠️  PCOV no está instalado."
    echo "Para instalarlo (Ubuntu/Debian):"
    echo "  sudo pecl install pcov"
    echo "  echo 'extension=pcov.so' | sudo tee -a /usr/local/etc/php/conf.d/pcov.ini"
    echo ""
    echo "En su lugar, puede usar Xdebug:"
    echo "  sudo apt-get install php-xdebug"
    echo ""
    echo "Los tests se ejecutarán sin cobertura."
    echo "La cobertura completa se analizará en CI (GitHub Actions)."
    echo ""
    composer test
    exit 0
fi

echo "✅ PCOV está disponible"
echo ""

# Ejecutar tests con cobertura
echo "Ejecutando tests con cobertura..."
composer test:coverage-clover

# Verificar si el archivo de cobertura se generó
if [ ! -f "coverage-clover.xml" ]; then
    echo "❌ Error: No se pudo generar el reporte de cobertura"
    exit 1
fi

# Extraer el porcentaje de cobertura (esto requiere herramientas adicionales)
echo ""
echo "======================================"
echo "Reporte de cobertura generado en:"
echo "  - coverage-html/index.html (HTML)"
echo "  - coverage-clover.xml (Clover/XML)"
echo "======================================"
echo ""

# Verificar si se alcanzó el umbral del 80%
echo "Para verificar el porcentaje de cobertura:"
echo "  1. Abrir coverage-html/index.html en el navegador"
echo "  2. Revisar el porcentaje global y por directorio"
echo "  3. Identificar archivos con cobertura < 80%"
echo ""

echo "Si la cobertura es < 80%, agregar tests para:"
echo "  - Observers (si existen)"
echo "  - Middleware (si existe)"
echo "  - Edge cases no cubiertos"
echo "  - Métodos helper en modelos"
echo ""

echo "Para mejorar la cobertura, ejecutar tests específicos:"
echo "  php artisan test --filter='test_method_name'"
echo ""
echo "Luego regenerar el reporte:"
echo "  composer test:coverage"
