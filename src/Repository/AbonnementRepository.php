<?php

namespace App\Repository;

use App\Entity\Abonnement;
use App\Entity\User;
use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
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
}
