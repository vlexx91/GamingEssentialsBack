<?php

namespace App\Entity;

use App\Repository\PedidoRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\TypeResolver;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'Pedido', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: PedidoRepository::class)]
class Pedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pedido:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['pedido:read'])]
    private ?DateTimeInterface $fecha = null;

    #[ORM\Column]
    #[Groups(['pedido:read'])]
    private ?bool $estado = null;

    #[ORM\Column(name:"pago_total" )]
    #[Groups(['pedido:read'])]
    private ?float $pagoTotal = null;


    #[ORM\ManyToOne(targetEntity: Perfil::class, inversedBy: 'pedidos')]
    #[ORM\JoinColumn(name: 'id_perfil', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)] // Cambia nullable según tus necesidades
    private ?Perfil $perfil = null;

    #[ORM\OneToMany(targetEntity: LineaPedido::class, mappedBy: 'pedido', cascade: ['remove'], orphanRemoval: true,fetch: "EAGER")]
    #[Groups(['pedido:read'])]
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

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getEstado(): ?bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getPagoTotal(): ?float
    {
        return $this->pagoTotal;
    }

    public function setPagoTotal(float $pagoTotal): static
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
