<?php

namespace App\Entity;

use App\Repository\DevolucionesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DevolucionesRepository::class)]
#[ORM\Table(name: 'devoluciones')]
class Devoluciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_devolucion = null;

    #[ORM\ManyToOne(targetEntity: Cliente::class, inversedBy: 'devoluciones')]
    #[ORM\JoinColumn(name: 'id_cliente', referencedColumnName: 'id_cliente', nullable: false)]
    private Cliente $cliente;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'id_producto', referencedColumnName: 'id_producto', nullable: false)]
    private Product $producto;

    #[ORM\Column(type: 'text')]
    private string $motivo;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fecha_devolucion;

    #[ORM\Column(type: 'string', length: 20)]
    private string $estado = 'solicitado';

    public function __construct()
    {
        $this->fecha_devolucion = new \DateTime();
    }

    public function getIdDevolucion(): ?int
    {
        return $this->id_devolucion;
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

    public function getProducto(): Product
    {
        return $this->producto;
    }

    public function setProducto(Product $producto): self
    {
        $this->producto = $producto;
        return $this;
    }

    public function getMotivo(): string
    {
        return $this->motivo;
    }

    public function setMotivo(string $motivo): self
    {
        $this->motivo = $motivo;
        return $this;
    }

    public function getFechaDevolucion(): \DateTimeInterface
    {
        return $this->fecha_devolucion;
    }

    public function setFechaDevolucion(\DateTimeInterface $fecha_devolucion): self
    {
        $this->fecha_devolucion = $fecha_devolucion;
        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): self
    {
        if (!in_array($estado, ['solicitado', 'aprobado', 'rechazado', 'procesado'])) {
            throw new \InvalidArgumentException('Estado inválido');
        }
        $this->estado = $estado;
        return $this;
    }

    public function getNombreProducto(): string
    {
        return $this->producto->getNombre();
    }

    public function getDescripcionProducto(): string
    {
        return $this->producto->getDescripcion();
    }

    public function getCategoriaProducto(): Categoria
    {
        return $this->producto->getCategoria();
    }
}