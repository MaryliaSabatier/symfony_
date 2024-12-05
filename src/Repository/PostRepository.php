<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Discussion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findBySearchQuery(string $query)
    {
        return $this->createQueryBuilder('p')
            ->where('p.contenu LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery(); // Assurez-vous de retourner la Query ici
    }

    /**
     * Trouve les derniers posts publiés
     *
     * @param int $limit Le nombre maximum de posts à récupérer
     * @return Post[] Retourne un tableau des derniers objets Post
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les posts avec leurs commentaires associés
     *
     * @return Post[] Retourne un tableau de posts avec leurs commentaires
     */
    public function findAllWithComments(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.commentaires', 'c') // Inclure les commentaires associés
            ->addSelect('c')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des posts liés à une discussion spécifique et contenant un mot-clé
     *
     * @param Discussion $discussion La discussion associée
     * @param string $query Le mot-clé à rechercher
     * @return Post[] Retourne un tableau des posts correspondants
     */public function findByDiscussionAndQuery(Discussion $discussion, string $query): array
    {
    return $this->createQueryBuilder('p')
        ->andWhere('p.discussion = :discussion')
        ->andWhere('p.contenu LIKE :query')
        ->setParameter('discussion', $discussion)
        ->setParameter('query', '%' . $query . '%')
        ->orderBy('p.dateCreation', 'DESC')
        ->getQuery()
        ->getResult();
    }
}
