<?php

declare(strict_types=1);

namespace App\Http\Helpers;

use App\Enums\VenueType;
use App\Models\Venue;

/**
 * @phpstan-import-type VenueMinimalForHtml from Venue
 * @phpstan-import-type VenueTypeCacheArray from VenueType
 */
class VenueHtmlHelper
{
    /**
     * @param list<VenueMinimalForHtml> $venues
     * @return non-empty-string
     */
    public static function buildListHtml(array $venues): string
    {
        if (count($venues) < 1) {
            return '<div id="paginateNoResults">No results</div>';
        }

        $output = '';

        foreach ($venues as $venue) {
            $output .= self::buildListVenueHtml($venue);
        }

        return $output;
    }

    /**
     * @param VenueTypeCacheArray $types
     * @return string
     */
    public static function buildTypesHtml(array $types): string
    {
        if (empty($types)) {
            return '';
        }

        $e = fn (mixed $value): string => HtmlEscaper::html($value);

        $html = '';

        foreach ($types as $type) {
            $html .= '<div class="' . $e($type['css_class']) . '">' . $e($type['name']) . '</div>';
        }

        return <<<HTML
            <div class="types-holder">
                $html
            </div>
            HTML;
    }

    /**
     * @param VenueMinimalForHtml $venue
     * @return non-empty-string
     */
    protected static function buildListVenueHtml(array $venue): string
    {
        $e = fn (mixed $value): string => HtmlEscaper::html($value);

        $typesHtml = self::buildTypesHtml($venue['types']);
        $timeHtml = '';
        $imageHtml = '';

        if ($venue['open']) {
            $timeHtml = '<span class="open">Open</span> till ' . $e($venue['change_time']);
        } else {
            $timeHtml = '<span class="closed">Opens ' . $e($venue['change_time']) . '</span>';
        }

        if ($venue['image'] !== null) {
            $imageHtml = '<img src="/images/small/' . $e($venue['image']) . '" loading="lazy" />';
        } else {
            $imageHtml = '<img src="/images/core/no-image.svg" />';
        }

        return <<<HTML
            <a class="venue" href="/venue/{$e($venue['ext_id'])}"> 
                $imageHtml
                <div class="body">
                    <div>{$e($venue['name'])}</div>
                    {$typesHtml}
                    <div>{$timeHtml}</div>
                </div>
            </a>

            HTML;
    }
}
