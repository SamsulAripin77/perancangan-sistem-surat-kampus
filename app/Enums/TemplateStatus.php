<?php

namespace App\Enums;

enum TemplateStatus: string
{
    case Draft = 'draft';
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
}
