<?php
// app/Enums/RecipienteEnum.php

namespace App\Enums;

use Mokhosh\FilamentKanban\Concerns\IsKanbanStatus;

enum RecipienteEnum: string
{
    use IsKanbanStatus;

    case quimica_sanguinea = 'quimica_sanguinea';
    case cuagulacion = 'cuagulacion';
    case hematologia = 'hematologia';
    case coprologia = 'coprologia';
    case uroanalisis = 'uroanalisis';
    case cultivo_secreciones = 'cultivo_secreciones'; 
    case extra = 'extra';

    public function getTitle(): string
    {
        return match($this) {
            self::quimica_sanguinea => 'Química Sanguínea',
            self::cuagulacion => 'Coagulación',
            self::hematologia => 'Hematología',
            self::coprologia => 'Coprología',
            self::uroanalisis => 'Uroanálisis',
            self::cultivo_secreciones => 'Bacteriología',
            self::extra => 'Extra',
        };
    }
}
