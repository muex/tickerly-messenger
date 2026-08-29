<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function save(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Only active games are public: a game an admin deactivated disappears
     * from the ticker lists and the JSON read models.
     *
     * @return Game[] Returns an array of Game objects
     */
    public function findNextGames(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.datetime > :val')
            ->andWhere('g.active = true')
            ->setParameter('val', new \DateTime('now'))
            ->orderBy('g.datetime', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Game[] Returns an array of Game objects
     */
    public function findLastGames(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.datetime < :val')
            ->andWhere('g.active = true')
            ->setParameter('val', new \DateTime('now'))
            ->orderBy('g.datetime', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * Every public game. The projector needs them all, not just the ten a list
     * shows, because each one has a snapshot of its own to keep current.
     *
     * @return Game[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.active = true')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * All games, newest first, with their owner joined for the admin list.
     *
     * @return Game[]
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('g')
            ->addSelect('o')
            ->join('g.owner', 'o')
            ->orderBy('g.datetime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{total: int, active: int, inactive: int}
     */
    public function countByStatus(): array
    {
        $total = (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $active = (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.active = true')
            ->getQuery()
            ->getSingleScalarResult();

        return ['total' => $total, 'active' => $active, 'inactive' => $total - $active];
    }
}
