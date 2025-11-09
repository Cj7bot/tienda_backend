<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'pedidos')]
class Order
{
    public const STATUS_PENDING = 'pendiente';
    public const STATUS_PROCESSING = 'procesando';
    public const STATUS_COMPLETED = 'completado';
    public const STATUS_CANCELLED = 'cancelado';

    #[ORM\Id]
    #[ORM\Column(name: 'id_pedido', type: 'string', length: 10)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(name: 'id_cliente', referencedColumnName: 'id_cliente', nullable: false)]
    private Cliente $client;

    #[ORM\Column(name: 'fecha_pedido', type: 'datetime')]
    private \DateTimeInterface $orderDate;

    #[ORM\Column(name: 'total', type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column(name: 'estado', type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'notas', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: DetallePedido::class, cascade: ['persist', 'remove'])]
    private Collection $detallesPedido;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getClient(): Cliente
    {
        return $this->client;
    }

    public function setClient(Cliente $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getOrderDate(): \DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            throw new \InvalidArgumentException('Estado inválido');
        }
        $this->status = $status;
        return $this;
    }

    /**
     * @return Collection<int, DetallePedido>
     */
    public function getDetallesPedido(): Collection
    {
        return $this->detallesPedido;
    }

    public function addDetallePedido(DetallePedido $detallePedido): self
    {
        if (!$this->detallesPedido->contains($detallePedido)) {
            $this->detallesPedido[] = $detallePedido;
            $detallePedido->setOrder($this);
        }

        return $this;
    }

    public function removeDetallePedido(DetallePedido $detallePedido): self
    {
        if ($this->detallesPedido->removeElement($detallePedido)) {
            // set the owning side to null (unless already changed)
            if ($detallePedido->getOrder() === $this) {
                $detallePedido->setOrder(null);
            }
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}
