<?php

namespace App\Repository;

use App\Entity\Abonnement;
use App\Entity\User;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 *
 * @method Abonnement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Abonnement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Abonnement[]    findAll()
 * @method Abonnement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    /**
     * Trouver tous les abonnements d'un utilisateur.
     *
     * @param User $user
     * @return Abonnement[] Retourne un tableau d'abonnements
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifier si un utilisateur est abonné à un événement donné.
     *
     * @param User $user
     * @param Evenement $evenement
     * @return bool
     */
    public function isUserSubscribedToEvent(User $user, Evenement $evenement): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->andWhere('a.user = :user')
            ->andWhere('a.evenement = :evenement')
            ->setParameter('user', $user)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupérer les IDs des événements auxquels un utilisateur est abonné.
     *
     * @param User $user
     * @return int[] Liste des IDs des événements
     */
    public function findSubscribedEventIdsByUser(User $user): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.evenement) as evenementId')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return array_column($result, 'evenementId');
    }

    /**
     * Récupérer les abonnements d'un utilisateur pour une discussion spécifique.
     *
     * @param User $user
     * @param int $discussionId
     * @return Abonnement[] Liste des abonnements pour une discussion
     */
    public function findByUserAndDiscussion(User $user, int $discussionId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.evenement', 'e')
            ->andWhere('a.user = :user')
            ->andWhere('e.discussion = :discussionId')
            ->setParameter('user', $user)
            ->setParameter('discussionId', $discussionId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifier si un utilisateur est abonné à une discussion.
     *
     * @param User $user
     * @param int $discussionId
     * @return bool
     */
    public function isUserSubscribedToDiscussion(User $user, int $discussionId): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->join('a.evenement', 'e')
            ->andWhere('a.user = :user')
            ->andWhere('e.discussion = :discussionId')
            ->setParameter('user', $user)
            ->setParameter('discussionId', $discussionId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
