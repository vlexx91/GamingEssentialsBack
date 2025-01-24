<?php

namespace App\Entity;

use App\Repository\PerfilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: PerfilRepository::class)]
#[ORM\Table(name: 'perfil', schema: 'gaming_essentials')]
class Perfil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 500)]
    private ?string $apellido = null;

    #[ORM\Column(length: 750)]
    private ?string $direccion = null;

    #[ORM\Column(length: 9)]
    private ?string $dni = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fecha_nacimiento = null;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Usuario::class, cascade: ["remove"])]
    #[ORM\JoinColumn(name: 'id_usuario', referencedColumnName: 'id', nullable: false)] // Cambia nullable según tus necesidades
    private ?Usuario $usuario = null;



//    #[ORM\OneToMany(targetEntity: LineaPedido::class, mappedBy: 'perfil', cascade: ['remove'])]
//    private Collection $lineaPedidos;
//
//    #[ORM\OneToMany(targetEntity: Pedido::class, mappedBy: 'perfil', cascade: ['remove'])]
//    private Collection $pedidos;
////
////
//    public function __construct()
//    {
//        $this->lineaPedidos = new ArrayCollection();
//        $this->pedidos = new ArrayCollection();
//    }
////
//    public function getLineaPedidos(): Collection
//    {
//        return $this->lineaPedidos;
//    }
////
//    public function getPedidos(): Collection
//    {
//        return $this->pedidos;
//    }



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

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): static
    {
        $this->direccion = $direccion;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): static
    {
        $this->dni = $dni;

        return $this;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fecha_nacimiento;
    }

    public function setFechaNacimiento(\DateTimeInterface $fecha_nacimiento): static
    {
        $this->fecha_nacimiento = $fecha_nacimiento;

        return $this;
    }

    // Cambiado de ?int a ?Usuario
    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    // Cambiado de int a ?Usuario
    public function setUsuario(?Usuario $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }
}
