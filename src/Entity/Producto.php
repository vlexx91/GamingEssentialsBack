<?php

namespace App\Entity;

use App\Enum\Categoria;
use App\Enum\Plataforma;
use App\Repository\ProductoRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'Producto', schema: 'gaming_essentials')]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['producto'])]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    #[Groups(['producto'])]
    private ?string $nombre = null;

    #[ORM\Column(length: 750)]
    #[Groups(['producto'])]
    private ?string $descripcion = null;

    //dato que faltaba
    #[ORM\Column(length: 900)]
    #[Groups(['producto'])]
    private ?string $imagen = null;

    #[ORM\Column]
    #[Groups(['producto'])]
    private ?bool $disponibilidad = null;

    #[ORM\Column(type: 'float', precision: 10, scale: 2)]
    #[Groups(['producto'])]
    private ?float $precio = null;

    #[ORM\Column(type: 'integer', enumType: Categoria::class)]
    #[Groups(['producto'])]
    private ?Categoria $categoria = null;

    #[ORM\Column(type: 'integer', enumType: Plataforma::class)]
    #[Groups(['producto'])]
    private ?Plataforma $plataforma = null;

    #[ORM\OneToMany(mappedBy: 'producto', targetEntity: LineaPedido::class)]
    #[Groups(['producto'])]
    private Collection $lineaPedidos;

    public function getLineaPedidos(): Collection
    {
        return $this->lineaPedidos;
    }

    public function setLineaPedidos(Collection $lineaPedidos): void
    {
        $this->lineaPedidos = $lineaPedidos;
    }




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function isDisponibilidad(): ?bool
    {
        return $this->disponibilidad;
    }

    public function setDisponibilidad(bool $disponibilidad): static
    {
        $this->disponibilidad = $disponibilidad;

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
    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(Categoria $categoria): static
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function getPlataforma(): ?Plataforma
    {
        return $this->plataforma;
    }

    public function setPlataforma(Plataforma $plataforma): static
    {
        $this->plataforma = $plataforma;

        return $this;
    }

    //getter y setter que faltaba
    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): void
    {
        $this->imagen = $imagen;
    }
}
