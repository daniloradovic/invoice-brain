<?php

namespace App\Enums;

enum WorkLogStatus: string
{
    case Unbilled = 'unbilled';
    case Billed = 'billed';
}
