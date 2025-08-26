<?php

declare(strict_types=1);

namespace App\Utils;

class Time
{
    /**
     * @param int<0, 6> $day
     * @return non-empty-string
     */
    public static function dayIntToString(int $day): string
    {
        return match ($day) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        };
    }

    /**
     * @param non-empty-string $time
     * @return non-empty-string
     */
    public static function militaryToCivilianTime(string $time): string
    {
        $time = new \DateTime($time);
        $time = $time->format('g:i a');

        // g - 12-hour format of an hour (1 to 12)
        // G - 24-hour format of an hour (0 to 23)
        // h - 12-hour format of an hour (01 to 12)
        // H - 24-hour format of an hour (00 to 23)
        // i - Minutes with leading zeros (00 to 59)
        // a - am / pm

        if ($time == '12:00 pm') {
            $time = 'midday';

        } elseif ($time == '12:00 am') {
            $time = 'midnight';
        }

        return $time;
    }
}
