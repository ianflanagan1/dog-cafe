<?php

use App\Http\DTOs\ListPageUrls;
use App\Http\Helpers\HtmlEscaper;

/**
 * @var array{
 *      urls: ListPageUrls,
 *      pageTitle: string,
 * } $p
 */

$e = fn ($value): mixed => HtmlEscaper::html($value);
?>
    <title><?= $e($p['pageTitle']) ?></title>
  <?= $p['urls']->prevPageUrl ? '<link rel="prev" href="' . $e($p['urls']->prevPageUrl) . '"/>' . "\n" : '' ?>
  <?= $p['urls']->nextPageUrl ? '<link rel="next" href="' . $e($p['urls']->nextPageUrl) . '"/>' . "\n" : '' ?>
    <script src="/js/main.js"></script>
    <script src="/js/ajax.js"></script>
    <script src="/js/search.js"></script>
    <script src="/js/panel.js"></script>
    <script src="/js/list.js" type="text/javascript" ></script>
    <script src="/js/paginate.js" type="text/javascript"></script>
    <link href="/css/main.css" rel="stylesheet" type="text/css" />
    <link href="/css/search.css" rel="stylesheet" type="text/css" />
    <link href="/css/panel.css" rel="stylesheet" type="text/css" />
    <link href="/css/venue-common.css" rel="stylesheet" type="text/css" />
    <link href="/css/paginate.css" rel="stylesheet" type="text/css" />
    <link href="/css/list.css" rel="stylesheet" type="text/css" />
    <link rel="canonical" href="<?= $e($p['urls']->canonical) ?>" />