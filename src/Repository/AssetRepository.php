<?php
//Работает напрямую с БД — извлекает, сохраняет, удаляет сущности -  
// как DAO (Data Access Object).
// Ты работаешь с сущностями напрямую: ищешь, сохраняешь, удаляешь.
namespace App\Repository;

use App\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    // Пример собственного метода
    public function findByAssetName($assetName)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.assetName = :val')
            ->setParameter('val', $assetName)
            ->getQuery()
            ->getResult();
    }
}
