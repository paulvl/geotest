<?php

namespace Gps;

use Exception;
use Carbon\Carbon;

class GpsDataHandler {

	const EARTH_RADIUS = 6378137; // Earth’s mean radius in meter
	const UNIT_M = "m";
	const UNIT_KM = "km";
	const VALUE_M = 1;
	const VALUE_KM = 1000;
	const KILOMETERS_PER_HOUR_TO_METERS_PER_SECOND = 0.27777777777777777777777777777778;
	const METERS_PER_SECOND_TO_KILOMETERS_PER_HOUR = 3.6;
	const BAD_SPEED_CACULATION = "bad speed calculation ='(";
	const SPEED_VALIDATION = "speed_validation";
	const DISTANCE_VALIDATION = "distance_validation";

	public $timeFieldName = 'created_at';

	public function address($orig_lat, $orig_lon, $format = null){
		return $this->getAddressData($orig_lat, $orig_lon, $format);
	}

	public function distance(array $arrayOfPoints, $unit = self::UNIT_KM)
	{
		$this->validateArrayOfCoordinates($arrayOfPoints, self::DISTANCE_VALIDATION);

		$totalPoints = count($arrayOfPoints);

		$distance = 0;

		for($i = 0; $i < $totalPoints; $i++) {
			if(isset($arrayOfPoints[$i+1])){
				$distance += $this->calculateDistance($arrayOfPoints[$i]['lat'], $arrayOfPoints[$i]['lng'], $arrayOfPoints[$i+1]['lat'], $arrayOfPoints[$i+1]['lng']);
			}else{
				break;
			}
		}
		switch ($unit) {
			case self::UNIT_M:
				$distance_convertion_unit = self::VALUE_M;
				$rounder = 0;
				break;

			case self::UNIT_KM:
				$distance_convertion_unit = self::VALUE_KM;
				$rounder = 3;
				break;
		}
		return round($distance / $distance_convertion_unit, $rounder);
	}

	public function speed(array $arrayOfCoordinatesAndTime, $speedInLastUnits = 1, $unit = self::UNIT_KM)
	{
		$this->validateArrayOfCoordinates($arrayOfCoordinatesAndTime, self::SPEED_VALIDATION);

		$arrayOfCoordinatesAndTime = array_reverse($arrayOfCoordinatesAndTime);

		$speeds = new Speeds;

		switch ($unit) {
			case self::UNIT_M:
				$distance_convertion_unit = self::VALUE_M;
				$speed_convertion_unit = 1;
				$time_convertion_unit = 60;
				break;

			case self::UNIT_KM:
				$distance_convertion_unit = self::VALUE_KM;
				$speed_convertion_unit =self::METERS_PER_SECOND_TO_KILOMETERS_PER_HOUR;
				$time_convertion_unit = 60;
				break;
		}

		$speedInLastUnits = $speedInLastUnits * $distance_convertion_unit;

		$totalPoints = count($arrayOfCoordinatesAndTime);

		$timeA = null;

		$distance = 0;
		$i = 0;

		while($i < $totalPoints) {
			$sumCounter = true;

			if(isset($arrayOfCoordinatesAndTime[$i+1])){

				$pointA = $arrayOfCoordinatesAndTime[$i];
				$pointB = $arrayOfCoordinatesAndTime[$i+1];
				$calculatedDistance = $this->calculateDistance($pointA['lat'], $pointA['lng'], $pointB['lat'], $pointB['lng']);
				$distance += $calculatedDistance;

				if(is_null($timeA)){
					$timeA = $pointA['created_at'];
				}

				$timeB = $pointB['created_at'];

				if($distance >= $speedInLastUnits){

					$time = $this->calculateTime($timeA, $timeB);
					$speed = $this->calculateSpeed($distance, $time);

					if($speed != self::BAD_SPEED_CACULATION)
					{
						$speeds->append([
							'distance' => $distance / $distance_convertion_unit,
							'time' => $time / $time_convertion_unit,
							'speed' => $speed * 3.6,
							'from' => $timeA,
							'until' => $timeB,
							'pointA' => $pointA['lat'].','.$pointA['lng'],
							'pointB' => $pointB['lat'].','.$pointB['lng']
						]);
						$timeA = null;
						$distance = 0;
						//debe de sumar el contador

					}else{
						unset($arrayOfCoordinatesAndTime[$i+1]);
   						$arrayOfCoordinatesAndTime = array_values($arrayOfCoordinatesAndTime);
   						$totalPoints = count($arrayOfCoordinatesAndTime);
						$distance -= $calculatedDistance;
						//debe de mantenerse igual el contador
						$sumCounter = false;
					}

				}

				if($sumCounter){
					$i++;
				}
				//debe de sumar el contador

			}else{
				break;
			}
		}

		if($distance != 0) {
			$time = $this->calculateTime($timeA, $timeB);
			$speed = $this->calculateSpeed($distance, $time);
			$speeds->append([
				'distance' => $distance / $distance_convertion_unit,
				'time' => $time / $time_convertion_unit,
				'speed' => $speed * 3.6,
				'from' => $timeA,
				'until' => $timeB,
				'pointA' => $pointA['lat'].','.$pointA['lng'],
				'pointB' => $pointB['lat'].','.$pointB['lng']
			]);
		}

		return $speeds;
	}

	public function isCoordinatesInPolygon($polygonPoints, $lat, $lng = null)
	{
		$polygon = new Polygon(null, $polygonPoints);
		return $polygon->contains($lat, $lng);
	}

	private function getAddressData($orig_lat, $orig_lon, $format)
	{		
		$url = "http://maps.google.com/maps/api/geocode/json?latlng=$orig_lat,$orig_lon&sensor=false";
	    //$data = json_decode(file_get_contents($url), true);
	    $data = json_decode(file_get_contents($url));

	    // $address = $data->results[0]->address_components[0]->types;

	    $ai = 0;

	    $address_components = $data->results[$ai]->address_components;

	    $hasStreetNumber = false;
	    $hasRoute = false;
	    $hasDistrict = false;
	    $hasProvince = false;
	    $hasRegion = false;

	    for ($i = 0; $i < count($address_components); $i++) {
	    	$ac = $address_components[$i];

	    	if(in_array('street_number', $ac->types))
	    	{
	    		if(!$hasStreetNumber){
	    			$street_number = $ac->long_name;
	    			$hasStreetNumber = true;
	    		}
	    	}

	    	if(in_array('route', $ac->types))
	    	{
	    		if(!$hasRoute){
	    			$route = $ac->long_name;
	    			$hasRoute = true;
	    		}
	    	}

	    	if ($i == (count($address_components)-1)) {
				if($hasStreetNumber && $hasRoute)
		    	{
		    		break;
		    	}else{
		    		if($ai >= 1)
		    		{
		    			$ai--;
		    			$address_components = $data->results[$ai]->address_components;
		    			$i = -1;
		    		}else{
		    			break;
		    		}
		    	}
		    }
	    }

	    $ai = 2;

	    $address_components = $data->results[$ai]->address_components;

	    for ($i = 0; $i < count($address_components); $i++) {
	    	$ac = $address_components[$i];

	    	if(in_array('locality', $ac->types))
	    	{
	    		if(!$hasDistrict){
	    			$district = $ac->long_name;
	    			$hasDistrict = true;
	    		}
	    	}

	    	if(in_array('administrative_area_level_2', $ac->types))
	    	{
	    		if(!$hasProvince){
	    			$province = $ac->long_name;
	    			$hasProvince = true;
	    		}
	    	}

	    	if(in_array('administrative_area_level_1', $ac->types))
	    	{
	    		if(!$hasRegion){
	    			$region = $ac->long_name;
	    			$hasRegion = true;
	    		}
	    	}

	    	if ($i == (count($address_components)-1)) {
				if($hasDistrict && $hasProvince && $hasRegion)
		    	{
		    		break;
		    	}else{
		    		if($ai >= 1)
		    		{
		    			$ai--;
		    			$address_components = $data->results[$ai]->address_components;
		    			$i = -1;
		    		}else{
		    			break;
		    		}
		    	}
		    }
	    }

	    $return_array['address'] = ((isset($route)) ? $route : '').' '.((isset($street_number)) ? $street_number : '');
	    $return_array['district'] = (isset($district)) ? $district : '';
	    $return_array['province'] = (isset($province)) ? $province : '';
	    $return_array['region'] = (isset($region)) ? $region : '';

	    $return_array['address'] = trim($return_array['address']);

	    $return_array['district'] = trim( preg_replace('/\d/', '', $return_array['district']) );
	    $return_array['district'] = trim( str_replace('District', '', $return_array['district']) );

	    $return_array['province'] = trim( preg_replace('/\d/', '', $return_array['province']) );
	    $return_array['region'] = trim( preg_replace('/\d/', '', $return_array['region']) );

	    $return_array['formatted_address'] = $data->results[0]->formatted_address;

	    if(is_null($format))
	    {
	    	return $return_array;
	    }elseif ($format === true) {
	    	return $return_array['address'] . ', ' . $return_array['district'] . ', ' . $return_array['province'] . ', ' . $return_array['region'];
	    }else{
	    	$glue = $format['glue'];
	    	$address = '';
	    	foreach ($format['order'] as $key => $value) {
	    		if(in_array($value, ['address', 'district', 'province', 'region'])){
	    			$address .= $key == 0 ? $return_array[$value] : $glue.$return_array[$value];
	    		}else{
					throw new Exception("order elements must be only this ones: 'address', 'district', 'province', 'region'", 1);	    			
	    		}
	    	}
	    	return $address;
	    }
	}

	private function calculateDistance($latA, $lngA, $latB, $lngB)
	{
		$dLat = deg2rad($latB - $latA);
		$dLong = deg2rad($lngB - $lngA);
		$a = sin($dLat / 2) * sin($dLat / 2) + cos( deg2rad($latA) ) * cos( deg2rad($latB) ) * sin($dLong / 2) * sin($dLong / 2);
		$c = 2 * atan2( sqrt($a), sqrt(1 - $a) );
		return self::EARTH_RADIUS * $c; // returns the distance in meter
	}

	private function calculateTime($timeA, $timeB)
	{
		$carbonTimeA = Carbon::createFromFormat('Y-m-d H:i:s', $timeA);
		$carbonTimeB = Carbon::createFromFormat('Y-m-d H:i:s', $timeB);
		return $carbonTimeA->diffInSeconds($carbonTimeB);
	}

	private function calculateSpeed($distance, $time)
	{
		if($time == 0){
			return self::BAD_SPEED_CACULATION;
		}

		return $distance / $time; // returns the speed in m/s
	}

	private function validateArrayOfCoordinates(array $arrayOfPoints, $type = "distance")
	{
		if(is_array($arrayOfPoints)){
			$totalPoints = count($arrayOfPoints);
			if($totalPoints < 2){
				throw new Exception("There should at least 2 coordinates on array", 1);
			}
			if($type === self::DISTANCE_VALIDATION){
				if(!isset($arrayOfPoints[0]['lat']) || !isset($arrayOfPoints[0]['lng'])){
					throw new Exception("Given array is not a LatLng array", 1);
				}
			}elseif ($type === self::SPEED_VALIDATION) {
				if(!isset($arrayOfPoints[0]['lat']) || !isset($arrayOfPoints[0]['lng']) || !isset($arrayOfPoints[0][$this->timeFieldName])){
					throw new Exception("Given array is not a LatLngTime array", 1);
				}
			}else{
				throw new Exception("Validator must specify is own type: 'distance' or 'speed'", 1);
			}
			return true;
		}else{
			throw new Exception("There should be an array of coordinates", 1);
		}
	}
}
