<?php

namespace App\Entity;

use App\Repository\PagoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PagoRepository::class)]
#[ORM\Table(name: 'pago')]
class Pago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_pago = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class, inversedBy: 'pagos')]
    #[ORM\JoinColumn(name: 'id_cliente', referencedColumnName: 'id_cliente', nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 100)]
    private string $apellido;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tarjeta;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto;

    #[ORM\Column(type: 'boolean')]
    private bool $estado = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_pago;

    public function __construct()
    {
        $this->fecha_pago = new \DateTime();
    }

    public function getIdPago(): ?int
    {
        return $this->id_pago;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function setCliente(Cliente $cliente): self
    {
        $this->cliente = $cliente;
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

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): self
    {
        $this->apellido = $apellido;
        return $this;
    }

    public function getTarjeta(): string
    {
        return $this->tarjeta;
    }

    public function setTarjeta(string $tarjeta): self
    {
        $this->tarjeta = $tarjeta;
        return $this;
    }

    public function getMonto(): string
    {
        return $this->monto;
    }

    public function setMonto(string $monto): self
    {
        $this->monto = $monto;
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

    public function isCompletado(): bool
    {
        return $this->estado;
    }

    public function getFechaPago(): \DateTimeInterface
    {
        return $this->fecha_pago;
    }

    public function setFechaPago(\DateTimeInterface $fecha_pago): self
    {
        $this->fecha_pago = $fecha_pago;
        return $this;
    }
}