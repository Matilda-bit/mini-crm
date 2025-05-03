<?php
//Работает напрямую с БД — извлекает, сохраняет, удаляет сущности -  
// как DAO (Data Access Object).
// Ты работаешь с сущностями напрямую: ищешь, сохраняешь, удаляешь.
namespace App\Repository;

use App\Entity\Trade;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Trade>
 *
 * @method Trade|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trade|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trade[]    findAll()
 * @method Trade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trade::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    
    public function findOpenTradesByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'open')
            ->getQuery()
            ->getResult();
    }

    public function findByUsers(array $users): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user IN (:users)')
            ->setParameter('users', $users)
            ->getQuery()
            ->getResult();
    }

    public function getAllForUserAndSubordinates(User $user, array $subordinates): array
    {
        if ($user->getRole() === 'ADMIN') {
            return $this->findAll();
        }

        $users = array_merge([$user], $subordinates);

        return $this->createQueryBuilder('t')
            ->where('t.user IN (:users)')
            ->setParameter('users', $users)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Trade[] Returns an array of Trade objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Trade
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
