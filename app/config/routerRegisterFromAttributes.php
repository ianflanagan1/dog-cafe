<?php

declare(strict_types=1);

use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\BasicController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\SuggestController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\VenueController;

return [
    BasicController::class,
    ListController::class,
    MapController::class,
    TownController::class,
    VenueController::class,
    SuggestController::class,
    LoginController::class,
    FavouriteController::class,
    AccountManagementController::class,
];
