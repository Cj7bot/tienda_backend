<?php

namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: 'categoria')]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['product:read'])]
    private ?int $id_categoria = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['product:read'])]
    private string $nombre;

    #[ORM\OneToMany(mappedBy: 'categoria', targetEntity: Product::class)]
    private Collection $productos;

    public function __construct()
    {
        $this->productos = new ArrayCollection();
    }

    public function getIdCategoria(): ?int
    {
        return $this->id_categoria;
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

    public function getProductos(): Collection
    {
        return $this->productos;
    }

    public function addProducto(Product $producto): self
    {
        if (!$this->productos->contains($producto)) {
            $this->productos->add($producto);
            $producto->setCategoria($this);
        }
        return $this;
    }

    public function removeProducto(Product $producto): self
    {
        if ($this->productos->removeElement($producto)) {
            if ($producto->getCategoria() === $this) {
                $producto->setCategoria(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}