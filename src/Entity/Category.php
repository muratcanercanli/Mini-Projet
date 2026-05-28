<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $listProduct;

    public function __construct()
    {
        $this->listProduct = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getListProduct(): Collection
    {
        return $this->listProduct;
    }

    public function addListProduct(Product $listProduct): static
    {
        if (!$this->listProduct->contains($listProduct)) {
            $this->listProduct->add($listProduct);
            $listProduct->setCategory($this);
        }

        return $this;
    }

    public function removeListProduct(Product $listProduct): static
    {
        if ($this->listProduct->removeElement($listProduct)) {
            // set the owning side to null (unless already changed)
            if ($listProduct->getCategory() === $this) {
                $listProduct->setCategory(null);
            }
        }

        return $this;
    }
}
