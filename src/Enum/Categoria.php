<?php

namespace App\Enum;

enum Categoria:int
{
    case SIN_CATEGORIA=0;
    case PEGI3=1;
    case PEGI7=2;
    case PEGI12=3;
    case PEGI16=4;
    case PEGI18=5;
    case PERIFERICOS=6;
}
