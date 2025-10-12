<?php

namespace App\Entity;

use App\Repository\ClienteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: 'clientes')]
class Cliente implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_cliente = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 100)]
    private string $apellido;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $dni = null;

    #[ORM\Column(type: 'boolean')]
    private bool $estado = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_registro;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: Pedido::class)]
    private Collection $pedidos;

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: Pago::class)]
    private Collection $pagos;

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: DetalleBoleta::class)]
    private Collection $detalleBoletas;

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: DetalleFactura::class)]
    private Collection $detalleFacturas;

    #[ORM\OneToMany(mappedBy: 'cliente', targetEntity: Devoluciones::class)]
    private Collection $devoluciones;

    public function __construct()
    {
        $this->fecha_registro = new \DateTime();
        $this->pedidos = new ArrayCollection();
        $this->pagos = new ArrayCollection();
        $this->detalleBoletas = new ArrayCollection();
        $this->detalleFacturas = new ArrayCollection();
        $this->devoluciones = new ArrayCollection();
    }

    public function getIdCliente(): ?int
    {
        return $this->id_cliente;
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

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): self
    {
        $this->apellido = $apellido;
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): self
    {
        $this->direccion = $direccion;
        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(?string $dni): self
    {
        $this->dni = $dni;
        return $this;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function isActivo(): bool
    {
        return $this->estado;
    }

    public function getFechaRegistro(): \DateTimeInterface
    {
        return $this->fecha_registro;
    }

    public function setFechaRegistro(\DateTimeInterface $fecha_registro): self
    {
        $this->fecha_registro = $fecha_registro;
        return $this;
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

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedido $pedido): self
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos->add($pedido);
            $pedido->setCliente($this);
        }
        return $this;
    }

    public function removePedido(Pedido $pedido): self
    {
        $this->pedidos->removeElement($pedido);
        return $this;
    }

    public function getPagos(): Collection
    {
        return $this->pagos;
    }

    public function addPago(Pago $pago): self
    {
        if (!$this->pagos->contains($pago)) {
            $this->pagos->add($pago);
            $pago->setCliente($this);
        }
        return $this;
    }

    public function removePago(Pago $pago): self
    {
        $this->pagos->removeElement($pago);
        return $this;
    }

    public function getDetalleBoletas(): Collection
    {
        return $this->detalleBoletas;
    }

    public function getDetalleFacturas(): Collection
    {
        return $this->detalleFacturas;
    }

    public function getDevoluciones(): Collection
    {
        return $this->devoluciones;
    }
}
