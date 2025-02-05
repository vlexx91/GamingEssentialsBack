create table usuario(
	id serial primary key,
	username varchar(500) not null,
	password varchar(500) not null,
	correo varchar(500) not null,
	rol int not null
);

create table perfil(
	id serial primary key,
	nombre varchar(200) not null,
	apellido varchar(500) not null,
	direccion varchar(750) not null,
	dni char(9) not null,
	fecha_nacimiento timeStamp(6) not null,
    telefono varchar(100) not null,
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
	categoria int not null
);

create table valoraciones(
	id serial primary key not null,
	estrellas int not null,
	comentario varchar(2000),
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

