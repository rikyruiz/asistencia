-- Add GPS coordinates and out-of-range flag for checkout
-- This allows tracking where users actually check out, even if outside permitted locations

ALTER TABLE asistencias
ADD COLUMN lat_salida DECIMAL(10,8) NULL COMMENT 'Latitud de la ubicación al marcar salida',
ADD COLUMN lon_salida DECIMAL(11,8) NULL COMMENT 'Longitud de la ubicación al marcar salida',
ADD COLUMN fuera_de_rango BOOLEAN DEFAULT FALSE COMMENT 'Indica si la salida fue marcada fuera del rango permitido';

-- Add index for queries that filter by out-of-range checkouts
CREATE INDEX idx_fuera_rango ON asistencias(fuera_de_rango);

-- Comment for documentation
ALTER TABLE asistencias COMMENT = 'Tabla de asistencias con tracking de GPS para entrada y salida';
