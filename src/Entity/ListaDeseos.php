<?php

namespace App\Entity;

use App\Repository\ListaDeseosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Producto;
use App\Entity\Usuario;

#[ORM\Entity(repositoryClass: ListaDeseosRepository::class)]
#[ORM\Table(name: 'lista_deseos', schema: 'gaming_essentials')]
class ListaDeseos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: 'listaDeseos')]
    #[ORM\JoinColumn(name:'id_usuario', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Usuario $usuario = null;

    #[ORM\ManyToOne(targetEntity: Producto::class, inversedBy: 'listaDeseos')]
    #[ORM\JoinColumn(name:'id_producto', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Producto $producto = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $notificacion = false;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $fechaAgregado;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): self
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function setProducto(?Producto $producto): void
    {
        $this->producto = $producto;
    }

    public function getFechaAgregado(): \DateTime
    {
        return $this->fechaAgregado;
    }

    public function setFechaAgregado(\DateTime $fechaAgregado): void
    {
        $this->fechaAgregado = $fechaAgregado;
    }

    public function __construct()
    {
        $this->fechaAgregado = new \DateTime();
    }

    public function isNotificacion(): bool
    {
        return $this->notificacion;
    }

    public function setNotificacion(bool $notificacion): void
    {
        $this->notificacion = $notificacion;
    }

}
