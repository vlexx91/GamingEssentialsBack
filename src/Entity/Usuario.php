<?php

namespace App\Entity;

use App\Enum\Rol;
use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Table(name: 'Usuario', schema: 'gaming_essentials')]
#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{



    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("usuario")]
    private ?int $id = null;


    #[ORM\Column(length: 500)]
    #[Groups("usuario")]
    private ?string $username = null;


    #[ORM\Column(length: 500)]
    #[Groups("usuario")]
    private ?string $password = null;

    #[ORM\Column(length: 500)]
    #[Groups("usuario")]
    private ?string $correo = null;

    #[ORM\Column(length: 900)]
    #[Groups("usuario")]
    private ?string $codigoVerificacion = null;

    #[ORM\Column]
    #[Groups("usuario")]
    private ?bool $verificado = null;

    #[ORM\Column]
    #[Groups("usuario")]
    private ?bool $activo = null;

    #[ORM\Column(length: 100)]
    #[Groups("usuario")]
    private ?string $rol = null;

    //ONE TO MANY de perfiles
    #[ORM\OneToOne(targetEntity: Perfil::class, mappedBy: 'usuario', cascade: ['persist', 'remove'])]
    private ?Perfil $perfil;





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

    public function getCodigoVerificacion(): ?string
    {
        return $this->codigoVerificacion;
    }

    public function setCodigoVerificacion(?string $codigoVerificacion): void
    {
        $this->codigoVerificacion = $codigoVerificacion;
    }

    public function getVerificado(): ?bool
    {
        return $this->verificado;
    }

    public function setVerificado(?bool $verificado): void
    {
        $this->verificado = $verificado;
    }

    public function getActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(?bool $activo): void
    {
        $this->activo = $activo;
    }


    public function getRol(): ?string
    {
        return $this->rol;
    }

    public function setRol(string $rol): static
    {
        $this->rol = $rol;

        return $this;
    }


    public function getRoles(): array
    {
        $roles = [];
        $roles[] = $this->rol;
        return $roles;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function isGranted($role){
        return in_array($role, $this->getRoles());
    }

}
