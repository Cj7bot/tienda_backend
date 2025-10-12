<?php

namespace App\Entity;

use App\Repository\DetalleFacturaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetalleFacturaRepository::class)]
#[ORM\Table(name: 'detalle_factura')]
class DetalleFactura
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_detalle_factura = null;

    #[ORM\ManyToOne(targetEntity: Pedido::class, inversedBy: 'detalleFacturas')]
    #[ORM\JoinColumn(name: 'id_pedido', referencedColumnName: 'id_pedido', nullable: false)]
    private Pedido $pedido;

    #[ORM\ManyToOne(targetEntity: Cliente::class, inversedBy: 'detalleFacturas')]
    #[ORM\JoinColumn(name: 'id_cliente', referencedColumnName: 'id_cliente', nullable: false)]
    private Cliente $cliente;

    #[ORM\Column(type: 'string', length: 20)]
    private string $numero_factura;

    #[ORM\Column(type: 'string', length: 11)]
    private string $ruc_cliente;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_emision;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $igv;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    public function __construct()
    {
        $this->fecha_emision = new \DateTime();
        $this->numero_factura = 'F' . date('YmdHis') . rand(100, 999);
    }

    public function getIdDetalleFactura(): ?int
    {
        return $this->id_detalle_factura;
    }

    public function getPedido(): Pedido
    {
        return $this->pedido;
    }

    public function setPedido(Pedido $pedido): self
    {
        $this->pedido = $pedido;
        return $this;
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

    public function getNumeroFactura(): string
    {
        return $this->numero_factura;
    }

    public function setNumeroFactura(string $numero_factura): self
    {
        $this->numero_factura = $numero_factura;
        return $this;
    }

    public function getRucCliente(): string
    {
        return $this->ruc_cliente;
    }

    public function setRucCliente(string $ruc_cliente): self
    {
        $this->ruc_cliente = $ruc_cliente;
        return $this;
    }

    public function getFechaEmision(): \DateTimeInterface
    {
        return $this->fecha_emision;
    }

    public function setFechaEmision(\DateTimeInterface $fecha_emision): self
    {
        $this->fecha_emision = $fecha_emision;
        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getIgv(): string
    {
        return $this->igv;
    }

    public function setIgv(string $igv): self
    {
        $this->igv = $igv;
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

    public function calcularTotales(): void
    {
        $subtotalFloat = (float)$this->subtotal;
        $this->igv = (string)($subtotalFloat * 0.18);
        $this->total = (string)($subtotalFloat + (float)$this->igv);
    }
}