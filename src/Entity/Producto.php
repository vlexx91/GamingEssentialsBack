<?php

namespace App\Entity;
use App\Repository\ProductoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'Producto', schema: 'gaming_essentials')]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 300)]
    private ?string $nombre = null;

    #[ORM\Column(length: 750)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private ?bool $disponibilidad = null;

    #[ORM\Column(type: 'float', precision: 10, scale: 2)]
    private ?float $precio = null;

    #[ORM\Column(type: 'integer')]
    private ?int $categoria = null;

    #[ORM\Column(type: 'integer')]
    private ?int $plataforma = null;


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
    public function getCategoria(): ?int
    {
        return $this->categoria;
    }

    public function setCategoria(int $categoria): static
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function getPlataforma(): ?int
    {
        return $this->plataforma;
    }

    public function setPlataforma(int $plataforma): static
    {
        $this->plataforma = $plataforma;

        return $this;
    }
}
