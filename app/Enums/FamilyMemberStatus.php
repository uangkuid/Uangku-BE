<?php

namespace App\Enums;

enum FamilyMemberStatus: string
{
    case Active = 'Active';
    case Revoked = 'Revoked';
    case Left = 'Left';
}
