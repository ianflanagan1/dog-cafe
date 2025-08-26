<?php

use App\Http\DTOs\ListPageUrls;
use App\Http\Helpers\HtmlEscaper;
use App\Http\Helpers\VenueHtmlHelper;
use App\Models\Town;

/**
 * @var array{
 *      page: int<1, max>,
 *      totalPages: int<0, max>,
 *      town: Town,
 *      cafe: bool,
 *      restaurant: bool,
 *      bar: bool,
 *      open_now: bool,
 *      urls: ListPageUrls,
 *      venues: list<array{
 *          name: non-empty-string,
 *          ext_id: non-empty-string,
 *          types: list<array{
 *              value: int,
 *              name: non-empty-string,
 *              css_class: non-empty-string,
 *          }>,
 *          image: ?non-empty-string,
 *          open: bool,
 *          change_time: non-empty-string,
 *      }>,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
$listHtml = VenueHtmlHelper::buildListHtml($p['venues']);
?>
<input type="hidden" id="cityUrlSetter" value="<?= $e($p['town']->nameUrl) ?>" />
<input type="hidden" id="currPageSetter" value="<?= $e($p['page']) ?>" />
<input type="hidden" id="totalPagesSetter" value="<?= $e($p['totalPages']) ?>" />
  <div id="pageContent">
    <div class="header"></div>
    <div id="header">
      <div>Dog-friendly places in <?= $e($p['town']->name) ?></div>
      <div class="mobile-only">
        <span id="filterButton">Filters</span>
      </div>
    </div>
    <div id="pageHolder">
      <div class="page" data-page="<?= $e($p['page']) ?>">
        <?= $p['page'] > 1 ? '<div class="page-title">Page ' . $e($p['page']) . '</div>' : '' ?>
        <?= $p['urls']->prevPageUrl ? '<noscript><a href="' . $e($p['urls']->prevPageUrl) . '">Previous page</a></noscript>' . "\n" : '' ?>
        <?= $listHtml ?>
        <?= $p['urls']->nextPageUrl ? '<noscript><a href="' . $e($p['urls']->nextPageUrl) . '">Next page</a></noscript>' . "\n" : '' ?>
      </div>
    </div>
  </div>
</div>
<div id="panel" class="mobile-hide">
  <div id="closePanelButton" class="mobile-only">
    <span class="close">✕</span>
  </div>
  <div class="section">
    <div class="title">Filters</div>
    <label class="check" for="typesCafes">
      <input type="checkbox" id="typesCafes" name="typesCafes" <?= $p['cafe'] ? 'checked ' : '' ?>/>
      <span>Cafes</span>
    </label>
    <label class="check" for="typesRestaurants">
      <input type="checkbox" id="typesRestaurants" name="typesRestaurants" <?= $p['restaurant'] ? 'checked ' : '' ?>/>
      <span>Restaurants</span>
    </label>
    <label class="check" for="typesBars">
      <input type="checkbox" id="typesBars" name="typesBars" <?= $p['bar'] ? 'checked ' : '' ?>/>
      <span>Bars/Pubs</span>
    </label>
  </div>
  <div class="section">
    <label class="check" for="openNow">
      <input type="checkbox" id="openNow" name="open_now" <?= $p['open_now'] ? 'checked ' : '' ?>/>
    <span>Open now</span>
    </label>
  </div>
  <!--
  <div class="section">
    <div id="getLocation">LOCATION</div>
  </div>
  <div class="section">
    <div class="title">Order by</div>
    <label class="radio" for="orderBySmart">
      <input type="radio" name="order_by" />
      <div>Smart</div>
    </label>
    <label class="radio" for="orderByDistance">
    <input type="radio" name="order_by" />
    <div>Distance</div>
    </label>
  </div>
  -->
</div>