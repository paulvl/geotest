<?php

namespace Gps;

use ArrayObject;

class Speeds extends ArrayObject
{

	const GREEN_STATE = "green";
	const YELLOW_STATE = "yellow";
	const ORANGE_STATE = "orange";
	const RED_STATE = "red";
	
	function __construct($array = null)
	{
		if(is_array($array)){
			parent::__construct($array);
		}
	}

	public function __call($func, $argv)
    {
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_')
        {
            throw new BadMethodCallException(__CLASS__.'->'.$func);
        }
        return call_user_func_array($func, array_merge(array($this->getArrayCopy()), $argv));
    }

	public function current($rounded = false){
		$speeds = array_fetch($this->array_values(), 'speed');
		return isset($speeds[0]) ? ($rounded ? round($speeds[0]) : $speeds[0]) : null;
	}

	public function max($rounded = false){
		$speeds = array_fetch($this->array_values(), 'speed');
		rsort($speeds);
		return isset($speeds[0]) ? ($rounded ? round($speeds[0]) : $speeds[0]) : null;
	}

	public function min($rounded = false){
		$speeds = array_fetch($this->array_values(), 'speed');
		sort($speeds);
		return isset($speeds[0]) ? ($rounded ? round($speeds[0]) : $speeds[0]) : null;
	}

	public function average($rounded = false){
		$speeds = array_fetch($this->array_values(), 'speed');
		$elements = count($speeds);
		$sum = array_sum($speeds);
		$average = $sum / $elements;
		return $rounded ? round($average) : $average;
	}

	public function statistics($rounded = false){
		return [
			'max_speed' => $this->max($rounded),
			'min_speed' => $this->min($rounded),
			'average_speed' => $this->average($rounded)
		];
	}

	public function timesOverSpeed($speedLimit)
	{		
		$speeds = array_fetch($this->array_values(), 'speed');
		rsort($speeds);
		$times = 0;
		foreach ($speeds as $speed) {
			if($speed >= $speedLimit)
				$times++;
		}
		return $times;
	}
}