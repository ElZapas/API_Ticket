CREATE TABLE Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    puesto VARCHAR(100),
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE Clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    empresa VARCHAR(255),
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE Tickets (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_usuario INT,
    descripcion TEXT NOT NULL,
    fecha_recepcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Abierto', 'En Proceso', 'Resuelto', 'Cerrado') NOT NULL DEFAULT 'Abierto',
    prioridad ENUM('Baja', 'Media', 'Alta', 'Crítica') NOT NULL DEFAULT 'Media',
    canal_recepcion ENUM('WhatsApp', 'Correo') NOT NULL,
    fecha_resolucion DATETIME,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
); 