<?php

namespace App\Helpers;

class DateHelper
{
    public static function formatDate($date, $format = 'Y-m-d'): string
    {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    }
}
