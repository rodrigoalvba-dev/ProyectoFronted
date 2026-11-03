-- Base de datos de la tienda "Agenda de Actividades"
-- Importar en phpMyAdmin dentro de la base de datos ya creada en tu hosting.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(120) NOT NULL,
  descripcion TEXT NULL,
  categoria   VARCHAR(30) NOT NULL DEFAULT 'diaria',
  precio      DECIMAL(10,2) NOT NULL,
  stock       INT NOT NULL DEFAULT 0,
  vendidos    INT NOT NULL DEFAULT 0,
  imagen      VARCHAR(255) NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL,
  paypal_order_id   VARCHAR(64) NOT NULL UNIQUE,
  paypal_capture_id VARCHAR(64) NULL,
  total             DECIMAL(10,2) NOT NULL,
  moneda            CHAR(3) NOT NULL DEFAULT 'MXN',
  estado            VARCHAR(20) NOT NULL DEFAULT 'pagado',
  pagador_nombre    VARCHAR(150) NULL,
  pagador_email     VARCHAR(150) NULL,
  creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedido_items (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT NOT NULL,
  producto_id     INT NULL,
  nombre          VARCHAR(120) NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  cantidad        INT NOT NULL,
  subtotal        DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Administrador inicial:  admin@tienda.local  /  Admin123!   (cámbiala después)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Administrador', 'admin@tienda.local', '$2y$10$YyVnsD37PcIXq5vMHzFi1.F5iJR0C4Ohzye9vHRnZHyVBuzcycgRW', 'admin');

-- Productos de ejemplo
INSERT INTO productos (nombre, descripcion, categoria, precio, stock, activo) VALUES
('Agenda Diaria 2027', 'Una página por día, tapa dura, 384 páginas con horarios de 6:00 a 22:00.', 'diaria', 289.00, 25, 1),
('Planner Semanal Minimal', 'Vista de semana en dos páginas, espacio para metas y notas. Papel de 100 g.', 'semanal', 219.00, 40, 1),
('Planner Estudiante', 'Calendario escolar, horario de clases, registro de tareas y exámenes.', 'estudio', 199.00, 30, 1),
('Agenda Ejecutiva', 'Pasta de piel sintética, cinta marcadora y bolsillo interior para tarjetas.', 'trabajo', 449.00, 12, 1),
('Planner de Hábitos', 'Seguimiento mensual de hábitos con casillas y reflexión semanal.', 'semanal', 179.00, 50, 1),
('Set de Stickers de Actividades', '300 stickers para marcar deporte, estudio, trabajo y vida social.', 'accesorios', 89.00, 100, 1),
('Plumas de Gel (paquete de 6)', 'Tinta negra de secado rápido, punta de 0.5 mm.', 'accesorios', 79.00, 80, 1),
('Agenda de Bolsillo', 'Formato compacto A6, vista mensual y semanal, ideal para llevar a todos lados.', 'diaria', 129.00, 3, 1);
