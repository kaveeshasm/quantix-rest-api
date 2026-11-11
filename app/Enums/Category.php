<?php

namespace App\Enums;

enum Category: string
{
    case IT_Supported = 'it_supported';
    case Equipment = 'equipment';
    case Maintenance = 'maintenance';
    case Access = 'access';
}