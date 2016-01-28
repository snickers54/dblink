<?php
class building {

	public function constructionTime($basetime, $level, $reductor)
	{
		$time = $basetime * pow(exp(1), $level * 0.3);
		$time -= ($time * ($reductor / 100));
		return $time;
	}

	public function constructionRessources($ressources_bases, $cost_augmentation, $level, $number, $reductor = 0)
	{
		foreach ($ressources_bases AS $key => $value)
		{
			$step = 1;
			while ($step < $level)
			{
				$base = $ressources_bases[$key];
				$ressources_bases[$key] += ($base * ($cost_augmentation / 100));
				$step++;
			}
			$ressources_bases[$key] *= $number;
			$ressources_bases[$key] -= $ressources_bases[$key] * ($reductor / 100);
		}
		return $ressources_bases;
	}

	public function checkConstructionsLevel($obj)
	{
		$all = $obj->getDatas();
		$timestamp = time();
		$bool = false;
		foreach ($all AS $code => $array)
			if (isset($array['construction']) && $array['construction'] !== NULL)
			{
				$diff = $timestamp - $all[$code]['construction']['start'];
				$all[$code]['construction']['time'] -= $diff;
				$all[$code]['construction']['start'] = $timestamp;
				if ($all[$code]['construction']['time'] <= 0)
				{
					$bool = true;
					$all[$code]['level'] = (!isset($all[$code]['level'])) ? 1 : $all[$code]['level'] + 1;
					$all[$code]['construction'] = NULL;
				}
				$obj->$code = $all[$code];
			}
		return $bool;
	}

	public function checkConstructionsNumber($obj)
	{
		$all = $obj->getDatas();
		$timestamp = time();
		$bool = false;
		foreach ($all AS $code => $array)
			if (isset($array['construction']) && $array['construction'] !== NULL)
			{
				$diff = $timestamp - $all[$code]['construction']['start'];
				$all[$code]['construction']['start'] = $timestamp;
				$all[$code]['construction']['time'] -= $diff;
				$new_number = (int) ceil($all[$code]['construction']['time'] / ($all[$code]['construction']['time_base'] + 1));
				$add = $all[$code]['construction']['number'] - $new_number;
				$all[$code]['construction']['number'] = $new_number;
				if ($add > 0)
					$bool = true;
				if (!isset($all[$code]['number']))
					$all[$code]['number'] = 0;
				$all[$code]['number'] += $add;
				if ($all[$code]['construction']['time'] <= 0)
				{
					$bool = true;
					$all[$code]['construction'] = NULL;
				}
				else
					$all[$code]['construction']['number'] -= $add;
				$obj->$code = $all[$code];
			}
		return $bool;
	}
	
	public function calculeGrowth($start, $pourcent, $level = 1)
	{
		for ($i = 1; $i < $level; $i++)
      		$start *= $pourcent;
   		return $start;
	}
}
?>