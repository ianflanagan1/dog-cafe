<?php

use App\Http\Helpers\HtmlEscaper;
use App\Http\Helpers\VenueHtmlHelper;

/**
 * @var array{
 *      venue: array{
 *          id: int<1, max>,
 *          ext_id: non-empty-string,
 *          name: non-empty-string,
 *          human_url: non-empty-string,
 *          town: non-empty-string,
 *          lat: float,
 *          lng: float,
 *          images: list<non-empty-string>,
 *          is_cafe: bool,
 *          is_restaurant: bool,
 *          is_bar: bool,
 *          fav: 0|1,
 *          types: list<array{
 *              value: int,
 *              name: non-empty-string,
 *              css_class: non-empty-string,
 *          }>,
 *          openClose: array{
 *              0: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              1: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              2: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              3: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              4: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              5: array{
 *                  day: string,
 *                  time: string,
 *              },
 *              6: array{
 *                  day: string,
 *                  time: string,
 *              },
 *          },
 *          street: ?non-empty-string,
 *          area: ?non-empty-string,
 *          locality: ?non-empty-string,
 *          region: ?non-empty-string,
 *          postcode: ?non-empty-string,
 *          public_phone: ?non-empty-string,
 *          website: ?non-empty-string,
 *      },
 *      loginRedirectUrl: ?string,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
$typesHtml = VenueHtmlHelper::buildTypesHtml($p['venue']['types']);
?>
<input type="hidden" id="latSetter" value="<?= $e($p['venue']['lat']) ?>" />
<input type="hidden" id="lngSetter" value="<?= $e($p['venue']['lng']) ?>" />
<input type="hidden" id="favSetter" value="<?= $e($p['venue']['fav']) ?>" />
<input type="hidden" id="extIdSetter" value="<?= $e($p['venue']['ext_id']) ?>" />
<div id="page">
    <div class="header"></div>
    <?php
        if (count($p['venue']['images']) > 0) {
            echo '<div id="images">';
            switch (count($p['venue']['images'])) {
                case 1:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>';
                    break;

                case 2:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                    </div>
                    ';
                    break;

                case 3:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][2]) . '" loading="lazy" />
                    </div>
                    ';
                    break;

                case 4:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][2]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][3]) . '" loading="lazy" />
                    </div>
                    ';
                    break;

                case 5:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][2]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][3]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][4]) . '" loading="lazy" />
                    </div>
                    ';
                    break;

                case 6:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div>
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][2]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][3]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][4]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][5]) . '" loading="lazy" />
                    </div>
                    ';
                    break;

                case 7:
                    echo '<div>
                        <img src="/images/mid/' . $e($p['venue']['images'][0]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][1]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][2]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][3]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][4]) . '" loading="lazy" />
                    </div>
                    <div class="double">
                        <img src="/images/mid/' . $e($p['venue']['images'][5]) . '" loading="lazy" />
                        <img src="/images/mid/' . $e($p['venue']['images'][6]) . '" loading="lazy" />
                    </div>
                    ';
                    break;
            }
            echo '</div>';
        }
?>
    <div id="content">
        <div id="title">
            <div><?= $e($p['venue']['name']) ?></div>
            <?php
            if ($p['loginRedirectUrl'] !== null) {
                echo '<a href="/login?location=' . $e($p['loginRedirectUrl']) . '"><img src="/images/core/fav-off.svg" /></a>';

            } else {
                if ($p['venue']['fav']) {
                    echo '<div id="favButton"><img src="/images/core/fav-on.svg" /></div>';
                } else {
                    echo '<div id="favButton"><img src="/images/core/fav-off.svg" /></div>';
                }
            }
?>
        </div>
        <?= $typesHtml ?>
        <div id="flexiColumns">
            <div>
                <div id="street"><?= $e($p['venue']['street']) ?></div>
                <div id="area">
                    <?php
            if ($p['venue']['area']) {
                echo '<span>' . $e($p['venue']['area']) . '</span>';
            }
?>
                    <span><?= $e($p['venue']['town']) ?></span>
                </div>
            </div>
            <div>
                <div id="phone"><?= $e($p['venue']['public_phone']) ?></div>
                <?php
if ($p['venue']['area']) {
    echo '<a href="' . $e($p['venue']['website']) . '">Website</a>';
}
?>
            </div>
        </div>
        <div id="strictColumns">
            <div>
                <table>
                    <?php
        foreach ($p['venue']['openClose'] as $openClose) {
            echo '<tr>
                                <td>' . $e($openClose['day']) . '</td>
                                <td>' . $e($openClose['time']) . '</td>
                            </tr>';
        }
?>
                </table>
            </div>
            <div id="map"></div>
        </div>
    </div>
</div>