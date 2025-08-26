<?php

use App\Http\Helpers\HtmlEscaper;
use App\Models\Town;

/**
 * @var array{
 *      town: Town,
 *      cafe: bool,
 *      restaurant: bool,
 *      bar: bool,
 *      open_now: bool,
 * } $p
 */

$e = fn (mixed $value): string => HtmlEscaper::html($value);
?>
<input type="hidden" id="latSetter" value="<?= $e($p['town']->lat) ?>" />
<input type="hidden" id="lngSetter" value="<?= $e($p['town']->lng) ?>" />
<input type="hidden" id="zoomSetter" value="<?= $e($p['town']->zoomLevel()) ?>" />
<div id="page">
    <div class="header"></div>
    <div id="main">
        <div id="panel" class="mobile-hide">
            <div class="mobile-only">
                <div id="closePanelButton" class="close">✕</div>
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
            <div class="section">
                <div id="getLocation">
                    <img src="/images/core/nearby.svg" />
                    <span>Nearby</span>
                </div>
            </div>
        </div>
        <div id="map"></div>
    </div>
</div>

<div id="venueBox">
    <div class="desktop-only">
        <span id="closeVenueBox" class="close">✕</span>
    </div>
    <div id="venueBoxContent"></div>
    <a id="seeVenue" class="desktop-only">See details</a>
</div>
<a id="filterButton">
    <div>Filters</div>
</a>