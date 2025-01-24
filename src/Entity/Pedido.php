<?php

namespace App\Entity;

use App\Repository\PedidoRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\TypeResolver;

#[ORM\Table(name: 'Pedido', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: PedidoRepository::class)]
class Pedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $fecha = null;

    #[ORM\Column]
    private ?bool $estado = null;

    #[ORM\Column(name:"pago_total" )]
    private ?float $pagoTotal = null;


    #[ORM\ManyToOne(targetEntity: Perfil::class, inversedBy: 'pedidos', cascade: ['remove'])]
    #[ORM\JoinColumn(name: 'id_perfil', referencedColumnName: 'id', nullable: false)] // Cambia nullable según tus necesidades
    private ?Perfil $perfil = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function isEstado(): ?bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getPagoTotal(): ?int
    {
        return $this->pagoTotal;
    }

    public function setPagoTotal(int $pagoTotal): static
    {
        $this->pagoTotal = $pagoTotal;

        return $this;
    }

    public function getPerfil(): ?Perfil
    {
        return $this->perfil;
    }

    public function setPerfil(?Perfil $perfil): static
    {
        $this->perfil = $perfil;

        return $this;
    }
}
