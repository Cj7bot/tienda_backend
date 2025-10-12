<?php

namespace App\Entity;

use App\Repository\DetallePedidoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetallePedidoRepository::class)]
#[ORM\Table(name: 'detalle_pedido')]
class DetallePedido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_detalle_pedido = null;

    #[ORM\ManyToOne(targetEntity: Pedido::class, inversedBy: 'detallesPedido')]
    #[ORM\JoinColumn(name: 'id_pedido', referencedColumnName: 'id_pedido', nullable: false)]
    private ?Pedido $pedido = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'id_producto', referencedColumnName: 'id_producto', nullable: false)]
    private Product $producto;

    #[ORM\Column(type: 'integer')]
    private int $cantidad;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $precio_unitario;

    public function getIdDetallePedido(): ?int
    {
        return $this->id_detalle_pedido;
    }

    public function getPedido(): ?Pedido
    {
        return $this->pedido;
    }

    public function setPedido(?Pedido $pedido): self
    {
        $this->pedido = $pedido;
        return $this;
    }

    public function getProducto(): Product
    {
        return $this->producto;
    }

    public function setProducto(Product $producto): self
    {
        $this->producto = $producto;
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

    public function getPrecioUnitario(): string
    {
        return $this->precio_unitario;
    }

    public function setPrecioUnitario(string $precio_unitario): self
    {
        $this->precio_unitario = $precio_unitario;
        return $this;
    }

    public function getSubtotal(): string
    {
        return (string)($this->cantidad * (float)$this->precio_unitario);
    }
}