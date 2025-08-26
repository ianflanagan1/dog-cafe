<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 *      name: string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
    <title><?= $e($p['name']) ?> - Dog Cafe</title>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/venue.js" type="text/javascript"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/search.css" rel="stylesheet" type="text/css" />
    <link href="/css/venue-common.css" rel="stylesheet" type="text/css" />
    <link href="/css/venue.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['canonical']) ?>" />