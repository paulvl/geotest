<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Gps\GpsDataHandler;

class TestController extends Controller
{

    public function getCase1()
    {
        $gpsHandler = new GpsDataHandler;
        return $gpsHandler->address(-12.086284, -76.988460);
    }

    public function getCase2()
    {
        $gpsHandler = new GpsDataHandler;
        $path = [
            ['lat' => -12.086284, 'lng' => -76.988460],
            ['lat' => -12.088595000000000, 'lng' => -77.016202900000000]
        ];

        return $gpsHandler->distance($path, GpsDataHandler::UNIT_KM);
    }

    public function getCase3()
    {
        $gpsHandler = new GpsDataHandler;
        $path = [
            ['lat' => -12.086284, 'lng' => -76.988460, 'created_at' => '2015-09-21 00:00:00'],
            ['lat' => -12.088595000000000, 'lng' => -77.016202900000000, 'created_at' => '2015-09-21 00:01:00'],
            ['lat' => -12.088595001000000, 'lng' => -77.016202910000000, 'created_at' => '2015-09-21 00:02:00']
        ];

        $speeds = $gpsHandler->speed($path);

        return $speeds->max(true);
    }

    public function getCase4()
    {
        $gpsHandler = new GpsDataHandler;
        $polygon = [
            [ 'lat' => -12.013129, 'lng' => -77.051926],
            [ 'lat' => -12.041335, 'lng' => -77.076645],
            [ 'lat' => -12.059466, 'lng' => -77.069778],
            [ 'lat' => -12.071553, 'lng' => -77.034760],
            [ 'lat' => -12.063495, 'lng' => -76.993561],
            [ 'lat' => -12.030590, 'lng' => -76.997681],
            [ 'lat' => -12.007085, 'lng' => -77.016220],
            [ 'lat' => -11.999025, 'lng' => -77.026520]
        ];
        
        // false
        // return dd($gpsHandler->isCoordinatesInPolygon($polygon, -11.956708156153624, -77.04540252685547));

        //true
        return dd($gpsHandler->isCoordinatesInPolygon($polygon, -12.045364372263712, -77.02995300292969));
    }
}
