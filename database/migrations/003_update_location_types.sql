-- Migration: Update location types ENUM
-- Date: 2025-11-02
-- Description: Add new location types (bodega, cooler, tienda, fabrica, sucursal)

ALTER TABLE ubicaciones
MODIFY COLUMN tipo_ubicacion ENUM(
    'oficina',
    'almacen',
    'bodega',
    'cooler',
    'tienda',
    'fabrica',
    'sucursal',
    'campo',
    'otro'
) DEFAULT 'oficina';
