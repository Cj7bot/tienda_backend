<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'usuarios')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 10)]
    private string $id_usuario;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_registro;

    #[ORM\Column(type: 'string', length: 10)]
    private string $estado = 'activo';

    #[ORM\OneToOne(mappedBy: 'usuario', targetEntity: Cliente::class)]
    private ?Cliente $cliente = null;

    public function getIdUsuario(): string
    {
        return $this->id_usuario;
    }

    public function setIdUsuario(string $id): self
    {
        $this->id_usuario = $id;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getFechaRegistro(): \DateTimeInterface
    {
        return $this->fecha_registro;
    }

    public function setFechaRegistro(\DateTimeInterface $fecha): self
    {
        $this->fecha_registro = $fecha;
        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): self
    {
        // unset the owning side of the relation if necessary
        if ($cliente === null && $this->cliente !== null) {
            $this->cliente->setUsuario(null);
        }

        // set the owning side of the relation if necessary
        if ($cliente !== null && $cliente->getUsuario() !== $this) {
            $cliente->setUsuario($this);
        }

        $this->cliente = $cliente;
        return $this;
    }

    public function eraseCredentials():void{}
}
