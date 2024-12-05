<?php

namespace App\Entity;

use App\Repository\DiscussionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DiscussionRepository::class)]
class Discussion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\OneToMany(mappedBy: 'discussion', targetEntity: Post::class, cascade: ['persist', 'remove'])]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'discussion', targetEntity: Evenement::class, cascade: ['persist', 'remove'])]
    private Collection $evenements;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isClosed = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isTemporary = false;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->evenements = new ArrayCollection();
    }

    // Getters et setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): self
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    public function setIsTemporary(bool $isTemporary): self
    {
        $this->isTemporary = $isTemporary;

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setDiscussion($this); // Assurez la cohérence de la relation inverse
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->posts->removeElement($post)) {
            // Définir la relation inverse à null si nécessaire
            if ($post->getDiscussion() === $this) {
                $post->setDiscussion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setDiscussion($this); // Assurez la cohérence de la relation inverse
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        if ($this->evenements->removeElement($evenement)) {
            // Définir la relation inverse à null si nécessaire
            if ($evenement->getDiscussion() === $this) {
                $evenement->setDiscussion(null);
            }
        }

        return $this;
    }

    public function areAllEventsClosed(): bool
{
    foreach ($this->evenements as $evenement) {
        if (!$evenement->isClosed()) {
            return false;
        }
    }
    return true;
}

}