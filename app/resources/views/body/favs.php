<?php

use App\Http\DTOs\ListPageUrls;
use App\Http\Helpers\HtmlEscaper;
use App\Http\Helpers\VenueHtmlHelper;

/**
 * @var array{
 *      page: int<1, max>,
 *      totalPages: int<0, max>,
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
<input type="hidden" id="currPageSetter" value="<?= $e($p['page']) ?>" />
<input type="hidden" id="totalPagesSetter" value="<?= $e($p['totalPages']) ?>" />
  <div id="pageContent">
    <div class="header"></div>
    <div id="header">
      <div>Saved favourites</div>
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