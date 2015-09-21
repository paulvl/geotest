<?php

namespace Gps;

use ArrayObject;
use Carbon\Carbon;

class Polygon extends ArrayObject
{
	public $name;
	
	function __construct($name= null, $array = null)
	{
		if(is_array($array)){
			parent::__construct($array);
		}
		$this->name = is_null($name) ? Carbon::now()->format('YmdHis') : $name;
	}

	public function contains($lat, $lng = null){
		if(is_array($lat)){
			$result = array();
			foreach ($lat as $latLng) {
				array_push(
					$result,
					[
						'contained' => $this->isPointInPolygon($latLng['lat'], $latLng['lng']),
						'coordinates' => $latLng
					]
				);
			}
			return $result;
		}
		return $this->isPointInPolygon($lat, $lng);
	}

	private function isPointInPolygon($lat, $lng)
	{
		$poly = $this->toArray();
		$points_polygon = count($poly);
		$poligon_lng = $this->lats();
		$poligon_lat = $this->lngs();

	  $i = $j = $c = 0;
	  for ($i = 0, $j = $points_polygon-1 ; $i < $points_polygon; $j = $i++) {
	    if ( (($poligon_lat[$i]  >  $lng != ($poligon_lat[$j] > $lng)) && ($lat < ($poligon_lng[$j] - $poligon_lng[$i]) * ($lng - $poligon_lat[$i]) / ($poligon_lat[$j] - $poligon_lat[$i]) + $poligon_lng[$i]) ) ){
	    	$c = !$c;
	    }
	  }
	  return $c == 0 ? false : $c;
	}

	public function toArray()
	{
		return json_decode(json_encode($this), true);
	}

	public function toJson()
	{
		return json_encode($this->toArray());
	}

	public function lats(){
		return array_pluck($this->toArray(), 'lat');
	}

	public function lngs(){
		return array_pluck($this->toArray(), 'lng');
	}

}