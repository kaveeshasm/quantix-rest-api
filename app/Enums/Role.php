<?php

namespace app\Enums;

enum Role: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Operations = 'operations';
}
