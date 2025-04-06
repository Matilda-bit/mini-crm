<?php 
namespace App\Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Platforms\MySQL80Platform;

class MySQLPlatform extends MySQL80Platform
{
    public function getDoctrineTypeMapping($dbType)
    {
        // Если тип ENUM, то возвращаем строку (или другой нужный тип)
        if ($dbType === 'enum') {
            return 'string';
        }

        return parent::getDoctrineTypeMapping($dbType);
    }
}
