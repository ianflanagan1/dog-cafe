<?php

use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      loggedIn: bool,
 *      appUserPicture: ?non-empty-string,
 *      search: bool,
 *      canonical: non-empty-string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);

if ($p['loggedIn']) {
    if ($p['appUserPicture'] !== null) {
        $loginBarHtml = '<a href="/settings"><img id="profile" src="/sh/user/' . $e($p['appUserPicture']) . '" />Account</a>';

    } else {
        $loginBarHtml = '<a href="/settings"><img id="profile" src="/images/core/user.svg" />Account</a>';
    }

    $loginDropdownHtml = '<a href="/settings">Account</a>';

} else {
    $loginBarHtml = '<a href="/login?location=' . urlencode($p['canonical']) . '">Login</a>';
    $loginDropdownHtml = $loginBarHtml;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
{{head}}
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <!--<meta name="google-site-verification" content="OIKZIPdivZvzsmxLwlVDzTZ3OMxhnav0OjRxksHD12M" />-->
  </head>
  <body>
    <nav id="desktopNav">
      <a href="/">
        <img src="/images/core/dog.svg" />
      </a>
      <?php
        if ($p['search']) {
            echo '<div id="searchButtonDesktop">
            <input type="text" placeholder="Search town" />
          </div>';
        }
?>
      <div class="stretch"></div>
      <a href="/list">List</a>
      <a href="/map">Map</a>
      <a href="/suggest">Suggest</a>
      <?= $loginBarHtml ?>
    </nav>
    <nav id="mobileNav">
      <a href="/">
        <img src="/images/core/dog.svg" />
      </a>
      <?php
  if ($p['search']) {
      echo '<div id="searchButtonMobile">
            <input type="text" placeholder="Search town" />
          </div>';
  } else {
      echo '<div class="stretch"></div>';
  }
?>
      <button id="dropdownMenuButton">☰</button>
    </nav>
    <div id="dropdown">
      <div>
        <span id="dropdownClose" class="close">✕</span>
      </div>
      <a href="/list">List</a>
      <a href="/map">Map</a>
      <a href="/suggest">Suggest a Feature</a>
      <div class="stretch"></div>
      <?= $loginDropdownHtml ?>
    </div>
    <?php if ($p['search']) {
        echo '<div id="search">
        <div>
          <div id="closeSearchButton" class="mobile-only">
            <span class="close">✕</span>
          </div>
          <input id="searchInput" type="text" placeholder="Search town" />
        </div>
        <!--<div id="searchNearby">Nearby</div>-->
        <div id="searchResults"></div>
      </div>
      <div id="searchCover"></div>';
    }
?>
{{body}}
  </body>
</html>