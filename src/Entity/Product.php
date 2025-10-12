<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/products', normalizationContext: ['groups' => 'product:read']),
        new Get(uriTemplate: '/products/{id}', normalizationContext: ['groups' => 'product:read']),
        new Post(uriTemplate: '/products'),
        new Put(uriTemplate: '/products/{id}'),
        new Delete(uriTemplate: '/products/{id}')
    ]
)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'productos')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['product:read'])]
    private ?int $id_producto = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['product:read'])]
    private string $nombre;

    #[ORM\Column(type: 'text')]
    #[Groups(['product:read'])]
    private string $descripcion;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: 'productos')]
    #[ORM\JoinColumn(name: 'id_categoria', referencedColumnName: 'id_categoria', nullable: false)]
    #[Groups(['product:read'])]
    private Categoria $categoria;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $imagen_producto = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['product:read'])]
    private string $precio;

    #[ORM\Column(type: 'integer')]
    #[Groups(['product:read'])]
    private int $stock = 0;

    public function getId(): ?int
    {
        return $this->id_producto;
    }

    public function getIdProducto(): ?int
    {
        return $this->id_producto;
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

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getCategoria(): Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(Categoria $categoria): self
    {
        $this->categoria = $categoria;
        return $this;
    }

    public function getImagenProducto(): ?string
    {
        return $this->imagen_producto;
    }

    public function setImagenProducto(?string $imagen_producto): self
    {
        $this->imagen_producto = $imagen_producto;
        return $this;
    }

    public function getPrecio(): string
    {
        return $this->precio;
    }

    public function setPrecio(string $precio): self
    {
        $this->precio = $precio;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }
}
