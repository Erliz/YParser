<?php
/**
 * ����� ������������ ��� ������ � ����������
 * @author Vladimir Kuptcov
 * @version 1.5
 */
class Image{
	/*
	typeOfResize - ��� �������
		0 - �� ��������
		1 - �������������� �� ������
		2 - �������������� �� ������
		3 - �������������� �� ������ � ������
		4 - ������ ��������� ��� ��������� ������
	
	$resizeSmall - ����������� ��������� ��������
	$needFormat  - ����� ������� ������ ��������� �����
	*/
	
	/**
	 * ������� ������� ��������
	 *
	 * @param string $src ������ �� ��������-��������
	 * @param string $dest ��� �����, � ������� ���������� �����. ���� �� ������� - ����� � �������
	 * @param integer $width ������ ��������� �����������s
	 * @param integer $height ������ ��������� �����������s
	 * @param integer $typeOfResize ������ �������.
	 *	0 - �� ��������
	 *	1 - �������������� �� ������
	 *	2 - �������������� �� ������
	 *	3 - �������������� �� ������ � ������
	 *	4 - ������ ��������� ��� ��������� ������
	 * @param string $needFormat ������ ��������, �� ��������� ��� ��, ��� � � ����� ���������
	 * @param integer $quality �������� �������� �� 0 �� 100
	 * @param boolean $resizeSmall ����������� �� ��������, ������� ������ �������� 
	 * @param string $errorString ������, � ������� ������������ ��������� �� ������
	 * @return boolean true � ������ �����, false � ������ ������
	 */
	static function imgResize($src, $dest = null, $width, $height, $typeOfResize = 3, $needFormat = null, $quality = 100, $resizeSmall = false, &$errorString = ''){
		
		if (!file_exists($src)){
			$errorString = '���� �� ����������';
			return false;
		}

			$size = getimagesize($src);

			if ($size === false){
				$errorString = '������������ ������ �����';
				return false;
			}

			$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));

			$icfunc = "imagecreatefrom" . $format;
			if (!function_exists($icfunc)){
				$errorString = '�� ���������� ������� ' . $icfunc;
				return false;
			}
		
			//���������� �������
			switch ($typeOfResize){
			case 0:
				$ratio = 1;
				break;
		
	  	case 1:
				$ratio = $width / $size[0];
				break;
		
			case 2:
	  			$ratio = $height / $size[1];
				break;
		
			case 3:
				$ratio = self::getScale($size[0], $size[1], $width, $height);
				break;
			case 4:
				$ratio = 1;
				break;
			}
			
			//���� ����� ��������� � � �� ��������� ���������, �� ������������� ������� 1
			if ($ratio > 1 && !$resizeSmall){
				$ratio = 1;
			}

			if ($typeOfResize != 4){
				$new_width  = $size[0] * $ratio;
				$new_height  = $size[1] * $ratio;
			} else {
				$new_width = $width;
				$new_height = $height;
			}

			$isrc = $icfunc($src);
			$idest = imagecreatetruecolor($new_width, $new_height);

		imagecopyresampled($idest, $isrc, 0,0, 0, 0, $new_width, $new_height, $size[0], $size[1]);

		//������������� ������ ������
		$needFormat = $needFormat ? $needFormat : $format;
		$distFunc = 'image' . $needFormat;
		if (!function_exists($distFunc)){
			$errorString = '������� ' . $distFunc . ' ����������.';
			return false;
		}
		
		//���� �� ������ �������� ���� - ������� � �������
		if (!$dest){
			header('Content-type: image/' . $needFormat);
		}
			$distFunc($idest, $dest, $quality);

			imagedestroy($isrc);
			imagedestroy($idest);


			return true;
	}
	
	private function getScale($width, $height, $newWidth, $newHeight){
		$xRatio = $newWidth / $width;
		$yRatio = $newHeight / $height;
		return min($xRatio, $yRatio);
	}
	
	/**
	 * ����������� �������� �� ���� ������ gif, png ��� jpg
	 *
	 * @param string $file ������ �� ����
	 * @param string $errorString ������ � ���������� �� ������
	 * @return boolean
	 */
	function isViewAbleFile($file, &$errorString = ''){
		$allowTypes = array(1, 2, 3);
		if (!file_exists($file)){
			$errorString = '���� �� ����������';
			return false;
		}
		$type = getimagesize($file);
		if (!in_array($type[2], $allowTypes)) return false;
		return true;
	}
}
?>