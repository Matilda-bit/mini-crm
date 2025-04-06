<?php
namespace App\Doctrine\DBAL\Types;

class RoleEnumType extends AbstractEnumType
{
    const ROLE_ENUM = 'role_enum';

    protected $values = ['USER', 'ADMIN'];

    public static function create()
    {
        return new self();
    }
}
