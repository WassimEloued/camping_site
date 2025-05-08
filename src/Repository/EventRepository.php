<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findByCreator(User $user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.creator = :user')
            ->setParameter('user', $user);

        if ($status !== null) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('e.startDate', 'ASC')
                 ->getQuery()
                 ->getResult();
    }

    public function findAvailableEvents(User $user): array
    {
        return $this->createAvailableEventsQuery($user)
            ->getQuery()
            ->getResult();
    }

    public function findAllApproved(): array
    {
        return $this->createStatusQueryBuilder('approved')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPending(): array
    {
        return $this->createStatusQueryBuilder('pending')
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcoming(int $maxResults = 5): array
    {
        return $this->createStatusQueryBuilder('approved')
            ->andWhere('e.startDate > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    private function createStatusQueryBuilder(string $status): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->leftJoin('e.creator', 'creator')
            ->addSelect('creator');
    }

    private function createAvailableEventsQuery(User $user): QueryBuilder
    {
        return $this->createStatusQueryBuilder('approved')
            ->andWhere('e.creator != :user')
            ->andWhere(':user NOT MEMBER OF e.participants')
            ->andWhere('e.startDate > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');
    }

    public function findSimilarEvents(Event $event, int $maxResults = 3): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.location LIKE :location')
            ->andWhere('e.id != :id')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('location', '%'.$event->getLocation().'%')
            ->setParameter('id', $event->getId())
            ->setParameter('status', 'approved')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}