create table usuario(
	id serial primary key,
	username varchar(500) not null,
	password varchar(500) not null,
	correo varchar(500) not null,
    verificado boolean not null,
    codigo_verificacion varchar(1000),
    activo boolean not null,
	rol varchar(100) not null
);

create table perfil(
	id serial primary key,
	nombre varchar(200) not null,
	apellido varchar(500) not null,
	direccion varchar(750) not null,
	dni char(9) not null,
	fecha_nacimiento timeStamp(6) not null,
    telefono varchar(9) not null,
    imagen varchar(900),
	id_usuario int not null,
    constraint fk_perfil_usuario foreign key (id_usuario) references usuario(id)
);


create table producto(
	id serial primary key not null,
	nombre varchar(300) not null,
	descripcion varchar(750) not null,
	disponibilidad boolean not null,
	plataforma int not null,
	imagen varchar(900) not null,
	precio float not null,
	categoria int not null,
    codigo_juego varchar(900) not null,
);

CREATE TABLE lista_deseos (
    id SERIAL PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lista_deseos_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_lista_deseos_producto FOREIGN KEY (id_producto) REFERENCES producto(id) ON DELETE CASCADE,
    UNIQUE (id_usuario, id_producto)
);


create table valoraciones(
	id serial primary key not null,
	estrellas int not null,
	comentario varchar(2000),
    activado boolean not null,
	id_usuario int not null,
	id_producto int not null,
    constraint fk_valoraciones_usuario foreign key (id_usuario) references usuario(id),
    constraint fk_valoraciones_producto foreign key (id_producto) references producto(id)
);


create table pedido(
	id serial primary key not null,
	fecha timeStamp(6) not null,
	estado boolean not null,
	pago_total float not null,
	id_perfil int not null,
    constraint fk_pedido_perfil foreign key (id_perfil) references perfil(id)
);


create table linea_pedido(
	id serial primary key not null,
	cantidad int not null,
	precio float not null,
	id_producto int not null,
	id_pedido int not null,
	constraint fk_linea_pedido_producto foreign key(id_producto) references producto(id),
	constraint fk_linea_pedido_pedido foreign key(id_pedido) references pedido(id)
);

