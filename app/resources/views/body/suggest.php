<?php

use App\DTOs\Error;
use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      formToken: non-empty-string,
 *      prefilled: array<string, mixed>,
 *      errors: list<Error>,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);

$suggest        = $e($p['prefilled']['suggest'] ?? '');

$errors = '';

foreach ($p['errors'] as $error) {
    $errors .= $error->message . '. ';
}
?>
<div id="page">
    <div class="header"></div>
    <div id=holder>
        <p>Suggest a new feature, or report a bug</p>
        <?= '<p class="error">' . $errors . '</p>' ?>
        <form action="" method="post">
            <input type="hidden" name="form_token" value="<?= $e($p['formToken']) ?>"/>
            <textarea name="suggest" id="suggest"><?= $suggest ?></textarea>
            <div id="remaining"></div>
            <input type="submit" value="Submit" id="submit" />
        </form>
    </div>
</div>