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

    /**
     * @return Expert[] Returns an array of Expert objects
     */
    public function findTopExperts(string $value = "", int $max = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('c')
             ->andWhere('c.fileName <> :defaultAvatar') 
             ->andWhere('c.status = :statusValid') 
             ->setParameter('statusValid', CandidateProfile::STATUS_VALID)
             ->setParameter('defaultAvatar', 'avatar-default.jpg')
             ->orderBy('c.id', 'ASC')
             ->setMaxResults($max)
             ->setFirstResult($offset)
             ->getQuery()
             ->getResult()
        ;
    }

    public function findTopRanked() : array
    {
         return $this->createQueryBuilder('c')
             ->select('c, COUNT(v.id) as HIDDEN num_views')
             ->leftJoin('c.vues', 'v')  
             ->andWhere('c.fileName <> :defaultAvatar') 
             ->andWhere('c.status = :statusFeatured') 
             ->setParameter('statusFeatured', CandidateProfile::STATUS_FEATURED)
             ->setParameter('defaultAvatar', 'avatar-default.jpg')
             ->groupBy('c')
             ->orderBy('num_views', 'DESC') 
             ->setMaxResults(12)
             ->getQuery()
             ->getResult()
         ;

     }
     
     public function findAllOrderedByIdDesc()
     {
         return $this->createQueryBuilder('j')
             ->orderBy('j.id', 'DESC')
             ->getQuery()
             ->getResult();
     }

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
