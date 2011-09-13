<?php
/**
 * @package htmlobjects
 *
 */

/**
 * Box
 *
 * @package htmlobjects
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2008, Alexander Kuballa
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
 */
 
class htmlobject_box extends htmlobject_base
{

/**
* Label (Title) of box
* @access public
* @var string
*/
var $label = '';
/**
* Label for input
* @access public
* @var string
*/
var $label_for = '';
/**
* content
* @access public
* @var object | string
*/
var $content = '';
/**
* css class for left box
* @access public
* @var string
*/
var $css_left = 'left';
/**
* css class for right box
* @access public
* @var string
*/
var $css_right = 'right';

/**
* extra content
* @access private
* @var array
*/
var $arr_content = array();

	/**
	 * init attribs
	 *
	 * @access protected
	 */
	function get_attribs() {
		$str = parent::get_attribs();
		if ($this->content == '')	{ $this->content = '&#160;'; }
		if ($this->css_left != '') 	{ $this->css_left = ' class="'.$this->css_left.'"'; }
		if ($this->css_right != '') { $this->css_right = ' class="'.$this->css_right.'"'; }
		return $str;
	}

	/**
	 * Get html element as string
	 *
	 * @access public
	 * @return string
	 */
	function get_string() {
	$_str = '';

		$str  = '';
		$text = $this->content;
		if(!is_array($text)) {
			$text = array($text);
		}
		reset($text);
		foreach($text as $value) {
			if(is_object($value)) {
				if(!isset($id)) {
					$this->id = $value->id.'_box';
					$id       = $value->id;
				}
				$str .= $value->get_string();
			} else { $str .= $value; }
		}
		if($this->label !== '') {
			$attr  = $this->get_attribs();
			$_str .= "\n<div".$attr.">";
			$_str .= "\n<div".$this->css_left.">";
			if($this->label_for != '') { $_str .= '<label for="'.$this->label_for.'">'.$this->label.'</label>'; }
			else if(isset($id)) { $_str .= '<label for="'.$id.'">'.$this->label.'</label>'; }
			else { $_str .= $this->label; }
			$_str .= "</div>";
			$_str .= "\n<div".$this->css_right.">";
			$_str .= $str;
			$_str .= "</div>";
			$_str .= "\n<div style=\"line-height:0px;height:0px;clear:both;\" class=\"floatbreaker\">&#160;</div>";
			$_str .= "\n</div>";
		} else {
			$_str .= $content;
		}
	return $_str;
	}

	/**
	 * Add additional content
	 *
	 * @access public
	 */
	function add($text) {
		$this->content[] = $text;
	}

}
?>
