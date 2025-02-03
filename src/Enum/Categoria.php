<?php

namespace App\Enum;

enum Categoria:int
{

    case PEGI3=0;
    case PEGI7=1;
    case PEGI12=2;
    case PEGI16=3;
    case PEGI18=4;
    case PERIFERICOS=5;
}
