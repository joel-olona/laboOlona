<?php

namespace App\Repository\Marketing;

use App\Entity\Marketing\Commission;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commission>
 *
 * @method Commission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commission[]    findAll()
 * @method Commission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Commission::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function countStatus(?string $status): int
    {
        if (!$status || $status == 'ALL') {
            return $this->countAll();
        }
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateCommissions($page, string $status = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c')
            ->groupBy('c.id') 
            ->addOrderBy('c.id', 'DESC');
            
        if ($status && $status != 'ALL') {
            $queryBuilder
                ->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            20,
            []
        );
    }
}
