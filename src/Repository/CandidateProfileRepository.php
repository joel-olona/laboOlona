<?php

namespace App\Repository;

use App\Entity\CandidateProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CandidateProfile>
 *
 * @method CandidateProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method CandidateProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method CandidateProfile[]    findAll()
 * @method CandidateProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CandidateProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidateProfile::class);
    }

//    /**
//     * @return CandidateProfile[] Returns an array of CandidateProfile objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CandidateProfile
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
