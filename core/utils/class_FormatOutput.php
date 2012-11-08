<?php
class FormatOutput {
	public static function formatSumm($summ){
		$num = $summ > 1000000 ? $summ / 1000 : $summ;
		$result = number_format($num, 2, ',', ' ');
		if ($summ > 1000000){
			$result .= ' тыс.';
		}
		return $result;
	}

}
?>