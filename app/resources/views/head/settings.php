<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<title>Settings</title>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/settings.js" type="text/javascript"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/settings.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['canonical']) ?>" />