<?php

namespace App\Entity;

use App\Repository\PedidoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedido')]
class Pedido
{
    public const STATUS_PENDING = 'pendiente';
    public const STATUS_PROCESSING = 'procesando';
    public const STATUS_COMPLETED = 'completado';
    public const STATUS_CANCELLED = 'cancelado';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_pedido = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class, inversedBy: 'pedidos')]
    #[ORM\JoinColumn(name: 'id_cliente', referencedColumnName: 'id_cliente', nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(type: 'integer')]
    private int $cantidad;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_pedido;

    #[ORM\Column(type: 'string', length: 20)]
    private string $estado = self::STATUS_PENDING;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $total = null;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: DetallePedido::class, cascade: ['persist', 'remove'])]
    private Collection $detallesPedido;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: DetalleBoleta::class)]
    private Collection $detalleBoletas;

    #[ORM\OneToMany(mappedBy: 'pedido', targetEntity: DetalleFactura::class)]
    private Collection $detalleFacturas;

    public function __construct()
    {
        $this->fecha_pedido = new \DateTime();
        $this->detallesPedido = new ArrayCollection();
        $this->detalleBoletas = new ArrayCollection();
        $this->detalleFacturas = new ArrayCollection();
    }

    public function getIdPedido(): ?int
    {
        return $this->id_pedido;
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

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): self
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    public function getFechaPedido(): \DateTimeInterface
    {
        return $this->fecha_pedido;
    }

    public function setFechaPedido(\DateTimeInterface $fecha_pedido): self
    {
        $this->fecha_pedido = $fecha_pedido;
        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        if (!in_array($estado, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            throw new \InvalidArgumentException('Estado inválido');
        }
        $this->estado = $estado;
        return $this;
    }

    public function getDetallesPedido(): Collection
    {
        return $this->detallesPedido;
    }

    public function addDetallePedido(DetallePedido $detallePedido): self
    {
        if (!$this->detallesPedido->contains($detallePedido)) {
            $this->detallesPedido->add($detallePedido);
            $detallePedido->setPedido($this);
        }
        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): self
    {
        if ($this->detallesPedido->removeElement($detallePedido)) {
            if ($detallePedido->getPedido() === $this) {
                $detallePedido->setPedido(null);
            }
        }
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

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(?string $total): self
    {
        $this->total = $total;
        return $this;
    }
}