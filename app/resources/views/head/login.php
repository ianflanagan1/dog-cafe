<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      canonical: string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<title>Login</title>
    <script src="/js/main.js"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/login.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="/login" />