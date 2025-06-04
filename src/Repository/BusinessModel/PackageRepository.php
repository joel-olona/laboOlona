<?php

namespace App\Repository\BusinessModel;

use App\Entity\BusinessModel\Package;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Package>
 *
 * @method Package|null find($id, $lockMode = null, $lockVersion = null)
 * @method Package|null findOneBy(array $criteria, array $orderBy = null)
 * @method Package[]    findAll()
 * @method Package[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Package::class);
    }
    
    public function paginatePackages($page, ?string $type = null): PaginationInterface
    {
        $queryBuilder = $this->createQueryBuilder('p')->select('p');
        $queryBuilder->addOrderBy('p.id', 'DESC');
        if ($type) {
            $queryBuilder->andWhere('p.type = :type')
                ->setParameter('type', $type);
        };

        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            10,
            [
                'distinct' => false,
                'shortFieldAllowList' => ['id', 'title', 'startEvent', 'endEvent', 'createdAt'],
            ]
        );
    }

    public function findContractPackages()
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->setParameter('type', 'CONTRAT');
    }

    public function findAbonnementPackages()
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->setParameter('type', 'ABONNEMENT');
    }

//    public function findOneBySomeField($value): ?Package
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
