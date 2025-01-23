<?php

namespace App\Entity;

use App\Enum\Rol;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

#[ORM\Table(name: 'Usuario', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
class Usuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 500)]
    private ?string $username = null;

    #[ORM\Column(length: 500)]
    private ?string $password = null;

    #[ORM\Column(length: 500)]
    private ?string $correo = null;

    #[ORM\Column(type: 'integer', enumType: Rol::class)]
    private ?Rol $rol = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): static
    {
        $this->correo = $correo;

        return $this;
    }

    public function getRol(): ?Rol
    {
        return $this->rol;
    }

    public function setRol(Rol $rol): static
    {
        $this->rol = $rol;

        return $this;
    }
}
