<?php

namespace App\DTO;

class CrearPedidoLineaPedidoDTO
{

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): void
    {
        $this->estado = $estado;
    }

    public function getPagoTotal(): float
    {
        return $this->pagoTotal;
    }

    public function setPagoTotal(float $pagoTotal): void
    {
        $this->pagoTotal = $pagoTotal;
    }

    public function getPerfilId(): int
    {
        return $this->perfilId;
    }

    public function setPerfilId(int $perfilId): void
    {
        $this->perfilId = $perfilId;
    }

    public function getLineasPedido(): array
    {
        return $this->lineasPedido;
    }

    public function setLineasPedido(array $lineasPedido): void
    {
        $this->lineasPedido = $lineasPedido;
    }
    public bool $estado;
    public float $pagoTotal;
    public int $perfilId;

    /** @var LineaPedidoDTO[] */
    public array $lineasPedido;
}

class LineaPedidoDTO
{
    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): void
    {
        $this->precio = $precio;
    }

    public function getProductoId(): int
    {
        return $this->productoId;
    }

    public function setProductoId(int $productoId): void
    {
        $this->productoId = $productoId;
    }
    public int $cantidad;
    public float $precio;
    public int $productoId;
}