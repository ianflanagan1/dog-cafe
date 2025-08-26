<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
    <title>Suggest a feature</title>
    <script src="/js/main.js"></script>
    <script src="/js/suggest.js" type="text/javascript"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/suggest.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['canonical']) ?>" />