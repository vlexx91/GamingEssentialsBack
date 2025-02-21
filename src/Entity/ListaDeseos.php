<?php

namespace App\Entity;

use App\Repository\ListaDeseosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name:'lista_deseos', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: ListaDeseosRepository::class)]
class ListaDeseos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: 'lista_deseos')]
    #[ORM\JoinColumn(name: 'id_usuario', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\ManyToOne(targetEntity: Producto::class, inversedBy: 'lista_deseos')]
    #[ORM\JoinColumn(name: 'id_producto', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Producto $producto;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTime $fechaAgregado;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): void
    {
        $this->usuario = $usuario;
    }

    public function getProducto(): Producto
    {
        return $this->producto;
    }

    public function setProducto(Producto $producto): void
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


}
