<?php

use App\Framework\View\HtmlEscaper;

/**
 * @var array{
 *      status: int<1, max>
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<div class="header"></div>
<div id="holder">
    <div><?= $e($p['status']) ?></div>
</div>