<?php
/**
 * скрипт подключет необходимые классы и файлы
 */
require_once("core/classes/class_Config.php");
require_once("core/boot.php");
require_once('core/libs/DbSimple/Generic.php');

function __autoload($className){
	if (file_exists(CLASSES_PATH . 'class_' . $className . '.php'))	require_once(CLASSES_PATH . 'class_' . $className . '.php');
	elseif (file_exists(UTILS_PATH . 'class_' . $className . '.php')) require_once(UTILS_PATH . 'class_' . $className . '.php');
	elseif (file_exists(ABSTRACTS_PATH . 'class_' . $className . '.php')) require_once(ABSTRACTS_PATH . 'class_' . $className . '.php');
	elseif (file_exists(INTERFACES_PATH . 'class_' . $className . '.php')) require_once(INTERFACES_PATH . 'class_' . $className . '.php');
}

?>