<?php
declare(strict_types=1);

/**
 * Helpers réutilisables pour la gestion des jours fermés et créneaux RDV.
 */
function get_easter_date(int $year): DateTimeImmutable
{
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return new DateTimeImmutable(sprintf("%04d-%02d-%02d", $year, $month, $day));
}

function get_holidays_for_year(int $year): array
{
    $easter = get_easter_date($year);
    $fixed = [
        sprintf("%04d-01-01", $year),
        sprintf("%04d-05-01", $year),
        sprintf("%04d-05-08", $year),
        sprintf("%04d-07-14", $year),
        sprintf("%04d-08-15", $year),
        sprintf("%04d-11-01", $year),
        sprintf("%04d-11-11", $year),
        sprintf("%04d-12-25", $year),
    ];

    $moving = [
        $easter->modify("+1 day")->format("Y-m-d"),   // Lundi de Paques
        $easter->modify("+39 days")->format("Y-m-d"), // Ascension
        $easter->modify("+50 days")->format("Y-m-d"), // Lundi de Pentecote
    ];

    return array_merge($fixed, $moving);
}

function is_closed_day(string $date): bool
{
    $day = DateTimeImmutable::createFromFormat("Y-m-d", $date);
    if (!$day) {
        return true;
    }

    $year = (int) $day->format("Y");
    $holidays = get_holidays_for_year($year);
    $is_sunday = $day->format("N") === "7";
    return $is_sunday || in_array($date, $holidays, true);
}

function get_slots_for_date(string $date): array
{
    if (is_closed_day($date)) {
        return [];
    }

    $day = DateTimeImmutable::createFromFormat("Y-m-d", $date);
    if (!$day) {
        return [];
    }

    $weekday = $day->format("N");
    if ($weekday === "6") {
        return ["09:00", "10:00", "11:00"];
    }

    return ["09:00", "10:00", "11:00", "14:00", "15:00", "16:00", "17:00"];
}

return null;
