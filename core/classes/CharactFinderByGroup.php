<?php
/**
 * ѕоиск характеристики дл€ группы
 *
 */
class CharactFinderByGroup extends CharactWrapper {

	public function __construct(Group $group, $onlyOn = true){
	    $this->strategy = new LibCompareStrategyFake();
//		$sql = 'SELECT TC.id, TC.title, IF (TC.value = "", TCV.value, TC.value) AS value, TC.value_type, TC.full_flag, TC.necessarily, TC.sel_type,
//						U.title unitName,
//						TCV.group_id, TCV.tovar_id,
//						TCV_SHOP.full_flag full_flag_overload
//					FROM tovar_charact TC
//					LEFT JOIN tovar_charact_unit U ON U.id = TC.id
//					LEFT JOIN tovar_charact_value AS TCV ON TC.id = TCV.charact_id
//					LEFT JOIN tovar_charact_value_' . SHOP_ID . ' AS TCV_SHOP ON TC.id = TCV_SHOP.charact_id
//					WHERE ' . ($onlyOn ? ' (TC.on_flag = 1 OR TCV_SHOP.on_flag = 1) AND ' : '') . ' TCV.group_id IN
//					(SELECT id FROM tovar_group
//					   WHERE left_id >= ' . $group->getLeftId() . '
//					   AND right_id <= ' . $group->getRightId() . ')
//					GROUP BY value
//					HAVING value != ""';
//
//
		$sql = 'SELECT TC.id, TC.title, TC.charact_desc, IF (TC.value = "", TCV_SHOP.value, TC.value) AS value, TC.value_type, TC.full_flag, TC.necessarily, TC.sel_type,
						U.title unitName,
						TCV_SHOP.group_id, TCV_SHOP.tovar_id,
						TC.full_flag full_flag_overload
                        FROM tovar_charact TC
                        LEFT JOIN tovar_charact_unit U ON U.id = TC.id
                        LEFT JOIN tovar_charact_value AS TCV_SHOP ON TC.id = TCV_SHOP.charact_id
                        LEFT JOIN tovar_tovar T ON T.id=TCV_SHOP.tovar_id

                        WHERE ' .
/**
 * _' . SHOP_ID . '
 * «акоментировано, чтобы не надо было устанавливать group_id
 * возникнут проблемы с производительностью - убрать комментарий, преобразовать базу
 */
//'(TCV_SHOP.group_id= ' . $group->getId() . '
//                        	OR
//                        	TC.necessarily > 0)
//                         AND
						'TCV_SHOP.on_flag = 1
                        AND use_for_search > 0
                        AND T.group_id IN
                        (SELECT id FROM tovar_group
                        	WHERE left_id >= ' . $group->getLeftId() . '
                        	AND right_id <= ' .  $group->getRightId() . ')
			
                        GROUP BY TC.title, value
                        HAVING value != ""
			ORDER BY TC.id';
		parent::__construct($sql);
        //echo $sql;
	}
}
?>