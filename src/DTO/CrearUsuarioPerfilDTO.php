<?php

namespace App\DTO;

use App\Enum\Rol;

class CrearUsuarioPerfilDTO
{
    private ?string $nombre= null;
    private ?string $username;
    private ?string $dni= null;
    private ?string $direccion = null;
    private ?\DateTimeInterface $fechaNacimiento = null;
    private ?string $apellidos = null;
    private ?string $email= null;
    private ?string $password= null;
    private ?Rol $rol= null;


    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(?string $dni): void
    {
        $this->dni = $dni;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): void
    {
        $this->direccion = $direccion;
    }

    public function getFechaNacimiento(): ?\DateTimeInterface
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeInterface $fechaNacimiento): void
    {
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(?string $apellidos): void
    {
        $this->apellidos = $apellidos;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getRol(): ?Rol
    {
        return $this->rol;
    }

    public function setRol(?Rol $rol): void
    {
        $this->rol = $rol;
    }


    /**
     * @param string|null $nombre
     * @param string|null $username
     * @param string|null $dni
     * @param string|null $direccion
     * @param \DateTimeInterface|null $fechaNacimiento
     * @param string|null $apellidos
     * @param string|null $email
     * @param string|null $password
     * @param Rol|null $rol
     */
//    public function __construct(?string $nombre, ?string $username, ?string $dni, ?string $direccion, ?\DateTimeInterface $fechaNacimiento, ?string $apellidos, ?string $email, ?string $password, ?Rol $rol)
//    {
//        $this->nombre = $nombre;
//        $this->username = $username;
//        $this->dni = $dni;
//        $this->direccion = $direccion;
//        $this->fechaNacimiento = $fechaNacimiento;
//        $this->apellidos = $apellidos;
//        $this->email = $email;
//        $this->password = $password;
//        $this->rol = $rol;
//    }


}