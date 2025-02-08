<?php

namespace App\Entity;

use App\Repository\ValoracionesRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'Valoraciones', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: ValoracionesRepository::class)]
class Valoraciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $estrellas = null;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $comentario = null;

    #[ORM\Column]
    private ?bool $activado = null;

//    #[Groups('valoraciones')]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "id_usuario",nullable: false)]
    private ?Usuario $usuario = null;

//    #[Groups('valoraciones')]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "id_producto",nullable: false)]
    private ?Producto $producto = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstrellas(): ?string
    {
        return $this->estrellas;
    }

    public function setEstrellas(string $estrellas): static
    {
        if ($estrellas < 0 || $estrellas > 5) {
            throw new InvalidArgumentException('El valor de estrellas debe estar entre 0 y 5.');
        }
        $this->estrellas = $estrellas;

        return $this;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): static
    {
        $this->comentario = $comentario;

        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

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

    public function getActivado(): ?bool
    {
        return $this->activado;
    }

    public function setActivado(?bool $activado): void
    {
        $this->activado = $activado;
    }
}
