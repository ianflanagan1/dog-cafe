<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      urlGoogle: non-empty-string,
 *      urlDiscord: non-empty-string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<div id="page">
    <div id="thirdParty">
        <a id="google" href="<?= $e($p['urlGoogle']) ?>">
            <div>
                <img src="/images/core/google.svg" />
            </div>
            <div>Sign in with Google</div>
        </a>
        <a id="discord" href="<?= $e($p['urlDiscord']) ?>">
            <div>
                <img src="/images/core/discord.svg" />
            </div>
            <div>Sign in with Discord</div>
        </a>
    </div>
</div>