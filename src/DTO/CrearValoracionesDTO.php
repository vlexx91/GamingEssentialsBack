<?php

namespace App\DTO;

class CrearValoracionesDTO
{
    private ?int $estrellas = null;

    private ?string $comentario = null;

    private ?int $id_usuario = null;

    private ?int $id_producto = null;

    public function getEstrellas(): ?int
    {
        return $this->estrellas;
    }

    public function setEstrellas(?int $estrellas): void
    {
        $this->estrellas = $estrellas;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): void
    {
        $this->comentario = $comentario;
    }

    public function getIdUsuario(): ?int
    {
        return $this->id_usuario;
    }

    public function setIdUsuario(?int $id_usuario): void
    {
        $this->id_usuario = $id_usuario;
    }

    public function getIdProducto(): ?int
    {
        return $this->id_producto;
    }

    public function setIdProducto(?int $id_producto): void
    {
        $this->id_producto = $id_producto;
    }

}