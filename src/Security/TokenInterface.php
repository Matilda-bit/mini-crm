<?php
///Users/polinaovras/Documents/project/cmr/mini-crm/src/Security/TokenInterface.php
namespace App\Security;

interface TokenInterface
{
    public function getToken(): string;
}
