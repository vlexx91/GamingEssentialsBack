<?php

namespace App\Entity;

use App\Repository\LineaPedidoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;


#[ORM\Table(name: 'linea_pedido', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: LineaPedidoRepository::class)]
class LineaPedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "integer")]
    private ?int $cantidad = null;

    #[ORM\Column]
    private ?float $precio = null;

    #[ORM\ManyToOne(targetEntity: Producto::class, inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name:'id_producto',nullable: false,onDelete: 'NULL')]
    private ?Producto $producto = null;

    #[ORM\ManyToOne(targetEntity: Pedido::class, cascade: ['remove'], inversedBy: 'lineaPedidos')]
    #[ORM\JoinColumn(name:'id_pedido',referencedColumnName: 'id',nullable: false)]
    private ?Pedido $pedido = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCantidad(): ?int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function setProducto(?Producto $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getPedido(): ?Pedido
    {
        return $this->pedido;
    }

    public function setPedido(?Pedido $pedido): static
    {
        $this->pedido = $pedido;

        return $this;
    }
}
