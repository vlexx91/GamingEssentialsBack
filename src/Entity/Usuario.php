<?php

namespace App\Entity;

use App\Enum\Rol;
use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Serializer\Attribute\Ignore;

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



    //ONE TO MANY de perfiles
    #[Ignore]
    #[ORM\OneToOne(targetEntity: Perfil::class, mappedBy: 'usuario', cascade: ['persist', 'remove'])]
    private ?Perfil $perfil;

//    public function __construct()
//    {
//        $this->perfil = new Perfil();
//    }
//    #[Ignore]
//    public function getPerfil(): Perfil
//    {
//        return $this->perfil;
//    }
//    public function setPerfil(?Perfil $perfil): self
//    {
//        $this->perfil = $perfil;
//
//        // Establecemos la relación en la entidad relacionada (Perfil)
//        if ($perfil !== null) {
//            $perfil->setUsuario($this);
//        }
//
//        return $this;
//    }



//
//    /**
//     * @var Collection<int, Valoraciones>
//     */
//    #[ORM\OneToMany(targetEntity: Valoraciones::class, mappedBy: 'usuario')]
//    private Collection $valoraciones;
//
//    public function __construct()
//    {
//        $this->valoraciones = new ArrayCollection();
//    }

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

//    /**
//     * @return Collection<int, Valoraciones>
//     */
//    public function getValoraciones(): Collection
//    {
//        return $this->valoraciones;
//    }
//
//    public function addValoracione(Valoraciones $valoracione): static
//    {
//        if (!$this->valoraciones->contains($valoracione)) {
//            $this->valoraciones->add($valoracione);
//            $valoracione->setUsuario($this);
//        }
//
//        return $this;
//    }
//
//    public function removeValoracione(Valoraciones $valoracione): static
//    {
//        if ($this->valoraciones->removeElement($valoracione)) {
//            // set the owning side to null (unless already changed)
//            if ($valoracione->getUsuario() === $this) {
//                $valoracione->setUsuario(null);
//            }
//        }
//
//        return $this;
//    }
}
