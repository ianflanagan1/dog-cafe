<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 *      status: int<1, max>,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
    <title><?= $e($p['status']) ?></title>
    <script src="/js/main.js"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/404.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['canonical']) ?>" />