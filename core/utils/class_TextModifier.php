<?php
class TextModifier
{
	static function translit($text, $remainingChars = '\._-', $translitSpace = '_'){
		$text = str_replace('�', 'ch', $text);
		$text = str_replace('�', 'sh', $text);
		$text = str_replace('�', 'sh', $text);
		$text = str_replace('�', 'yu', $text);
		$text = str_replace('�', 'ya', $text);
		$text = str_replace('�', '', $text);
		$text = str_replace('�', '', $text);
		$text = str_replace(' ', $translitSpace, $text);

		$text = strtr(strtolower($text),
					'�������������������������',
					'abvgdeejziiklmnoprstufhcie');
		$text = preg_replace('/[^' . $translitSpace . 'a-z0-9' . $remainingChars . ']/','',$text);
		return $text;
	}
	

	// "�����������" ������� E-mail.
	static function emailActivate($text){
		return preg_replace(
						  '{
    							[\w-.]+             # ��� �����
    							@
    							[\w-]+(\.[\w-]+)*   # ��� �����
  							}xs',
  							'<a href="mailto:$0">$0</a>',
  						$text
  						);
	}
	
	// �������� ������ �� �� HTML-����������� ("������������ ������").
	static function hrefActivate($text) {
		// ������� ��������� ������ ��� preg_replace_callback().
		if (!function_exists('hrefCallback')){
			function hrefCallback($p) {
	  			// ����������� ����������� � HTML-�������������.
  				$name = htmlspecialchars($p[0]);
  				// ���� ��� ���������, ��������� ��� � ������ ������.  
  				$href = !empty($p[1])? $name : 'http://' . $name;
	  			// ��������� ������.
  				return '<a href="' . $href . '">' . $name . '</a>';
			}
		}
  		return preg_replace_callback(
    		'{
      			(?:
        		(\w+://)          # �������� � ����� �������
        		|                 # - ��� -
        		www\.             # ������ ���������� �� www
      			)
      			[\w-]+(\.[\w-]+)*   # ��� �����
      			(?: : \d+)?         # ���� (�� ����������)
      			[^<>"\'()\[\]\s]*   # URI (�� ��� ������� � ������)
      			(?:                 # ��������� ������ ������ ����...
          		(?<! [[:punct:]] )  # �� �����������
        		| (?<= [-/&+*]     )  # �� ��������� ��������� �� -/&+*
      			)
    		}xis',
    		"hrefCallback",
    	$text);
    }
	
	//��������� ������� � ������ ������� � ���������� ��������
	//���������� true  � ������ ����������� �������� �� ���������� ������
	static function checkMultiLanguage($text){
		$engStringSmall = 'abcdefghigklmnopqrstuvwxyz';
		$engStringBig = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$rusStringSmall = '��������������������������������';
		$rusStringBig = '�����Ũ��������������������������';
		if (strpbrk($text, $engStringSmall) != false){
			return true;
		}
		if (strpbrk($text, $engStringBig) != false){
			return true;
		}
		if (strpbrk($text, $rusStringSmall) != false){
			return true;
		}
		if (strpbrk($text, $rusStringBig) != false){
			return true;
		}
		return false;
	}
}
?>