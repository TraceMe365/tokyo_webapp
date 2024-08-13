<?php

use App\Http\Controllers\WialonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(WialonController::class)->group(function () {
    Route::get('/getSessionEID', 'getSessionEID');
    Route::get('/getAllLocations', 'getAllLocations');
    Route::get('/reportOnePlantDurations', 'reportOnePlantDurations');
    Route::get('/reportOneSiteDurations', 'reportOneSiteDurations');
    Route::get('/getGeofenceGroups', 'getGeofenceGroups');
    Route::get('/getReportOneSingleLocation', 'getReportOneSingleLocation');
    Route::post('/getReportOne', 'getReportOne');
    Route::get('/getPlants', 'getPlants');
    Route::post('/getReportTwo', 'getReportTwo');
    Route::post('/getReportThree', 'getReportThree');
    Route::get('/getReportTwoLocationIds', 'getReportTwoLocationIds');
    Route::get('/getReportTwoFirstTruckInTime', 'getReportTwoFirstTruckInTime');
});