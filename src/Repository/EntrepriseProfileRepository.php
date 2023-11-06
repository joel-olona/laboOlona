<?php

namespace App\Repository;

use App\Entity\EntrepriseProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntrepriseProfile>
 *
 * @method EntrepriseProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method EntrepriseProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method EntrepriseProfile[]    findAll()
 * @method EntrepriseProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntrepriseProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntrepriseProfile::class);
    }

//    /**
//     * @return EntrepriseProfile[] Returns an array of EntrepriseProfile objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EntrepriseProfile
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
