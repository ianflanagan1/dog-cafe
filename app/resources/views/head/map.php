<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
    <title>Map</title>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/panel.js"></script>
    <script src="/js/map.js"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/search.css" rel="stylesheet" type="text/css" />
    <link href="/css/panel.css" rel="stylesheet" type="text/css" />
    <link href="/css/venue-common.css" rel="stylesheet" type="text/css" />
    <link href="/css/map.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['canonical']) ?>" />