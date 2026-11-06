<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The iCalendar feed (RFC 5545) for Apple Calendar, Google Calendar and the rest.
 */
class Calendar
{
    public function __construct(private Timeline $timeline) {}

    public function ics(User $user): string
    {
        $now = CarbonImmutable::now('UTC');
        $timezone = config('hoot.timezone');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//kimiyu//Hoot//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Hoot',
            'X-WR-CALDESC:New episodes and movie releases you follow',
            'REFRESH-INTERVAL;VALUE=DURATION:PT6H',
            'X-PUBLISHED-TTL:PT6H',
        ];

        $releases = $this->timeline->releases($user, $now->subDays(30), $now->addDays(config('hoot.days_ahead')), $timezone);

        foreach ($releases as $release) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$release['key'].'@hoot.kimiyu.ch';
            $lines[] = 'DTSTAMP:'.$now->format('Ymd\THis\Z');

            if ($release['kind'] === 'episodes') {
                $episodes = $release['episodes'];
                $first = $episodes->first();
                $minutes = max(15, (int) $episodes->sum(fn ($episode) => $episode->runtime ?? 30));
                $start = $first->airs_at->utc();

                $lines[] = 'DTSTART:'.$start->format('Ymd\THis\Z');
                $lines[] = 'DTEND:'.$start->addMinutes(min($minutes, 240))->format('Ymd\THis\Z');
                $lines[] = 'SUMMARY:'.self::escape($release['show']->name.' '.self::codes($episodes));
                $description = $episodes->count() === 1
                    ? ($first->name ?? '')
                    : $episodes->count().' episodes';
                $lines[] = 'DESCRIPTION:'.self::escape(trim($description."\n".($release['show']->channel ?? '')));
                if ($first->url) {
                    $lines[] = 'URL:'.$first->url;
                }
            } else {
                $movie = $release['movie'];
                $date = $release['at'];
                $label = $release['kind'] === 'digital' ? 'digital' : 'in cinemas';
                $country = $movie->{$release['kind'].'_country'};

                $lines[] = 'DTSTART;VALUE=DATE:'.$date->format('Ymd');
                $lines[] = 'DTEND;VALUE=DATE:'.$date->addDay()->format('Ymd');
                $lines[] = 'SUMMARY:'.self::escape($movie->title.' ('.$label.')');
                $lines[] = 'DESCRIPTION:'.self::escape('First '.$label.($country ? " ({$country})" : ''));
                $lines[] = 'URL:'.$movie->url();
                $lines[] = 'TRANSP:TRANSPARENT';
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    /** "S02E01" or "S02E01–E08". */
    public static function codes($episodes): string
    {
        $first = $episodes->first()->code();
        if ($episodes->count() === 1) {
            return $first;
        }
        $last = $episodes->last();

        return $last->season === $episodes->first()->season && $last->number !== null
            ? $first.'–'.sprintf('E%02d', $last->number)
            : $first.'–'.$last->code();
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\\,', '\\n', '\\n'], $text);
    }

    /** Lines longer than 75 bytes are folded onto continuation lines that start with a space. */
    private static function fold(string $line): string
    {
        $folded = '';
        $limit = 75;
        while (strlen($line) > $limit) {
            $cut = $limit;
            // Don't split a multi-byte character.
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $folded .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
            $limit = 74; // the leading space counts
        }

        return $folded.$line;
    }
}
