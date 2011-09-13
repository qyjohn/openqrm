<?php
/**
 * @package htmlobjects
 *
 */  


/**
 * Div
 *
 * @package htmlobjects
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2008, Alexander Kuballa
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
 */
class htmlobject_div extends htmlobject_base
{
/**
* text
* @access private
* @var string
*/
var $text = '';

	/**
	 * Get html element as string
	 *
	 * @access public
	 * @return string
	 */	
	function get_string() {
		$_str = '';
		$str = '';
		$text = $this->text;
		if(!is_array($text)) {
			$text = array($text);
		}

    	$k = array_keys($text);
    	$s = sizeOf($k);
		reset($text);

		for($i = 0; $i < $s; ++$i) {
			$value = $text[$k[$i]];
			if(is_object($value)) {
				$str .= $value->get_string();
			} else {
				$str .= $value;
			}
		}

		$attribs = $this->get_attribs();
		$_str = "\n<div$attribs>$str</div>";
	return $_str;
	}

	function add($text) {
		$this->text[] = $text;
	}

}

?>
