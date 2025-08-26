<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      logoutFormToken: non-empty-string,
 *      deleteFormToken: non-empty-string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<div id="page">
  <div class="header"></div>
  <div id="holder">
    <a href="/favs">Favourite Venues</a>
    <div>
      <form method="POST" action="/logout">
        <input type="hidden" name="logout_form_token" value="<?= $e($p['logoutFormToken']) ?>"/>
        <button type="submit" aria-label="Logout" class="link-style">Logout</button>
      </form>
    </div>
    <div>
      <form id="delete-account-form" method="POST" action="/account">
        <input type="hidden" name="delete_form_token" value="<?= $e($p['logoutFormToken']) ?>"/>
        <button type="submit" aria-label="Delete account" class="link-style">Delete Account</button>
      </form>
    </div>
  </div>
</div>