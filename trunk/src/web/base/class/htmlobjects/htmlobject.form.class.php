<?php
/**
 * @package htmlobjects
 *
 */

/**
 * Form
 *
 * @package htmlobjects
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2008, Alexander Kuballa
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
 */
class htmlobject_form extends htmlobject_base
{
/**
* uri
* @access public
* @var string
*/
var $action = '';
/**
* mime type
* @access public
* @var string
*/
var $enctype = '';
/**
* Post/Get
* @access public
* @var string
*/
var $method = '';
/**
* Attribute name
* @access public
* @var string
*/
var $name = '';
/**
* target
* @access public
* @var string
*/
var $target = '';
/**
* form elements
* @access public
* @var string
*/
var $elements = array();

	/**
	 * init attribs
	 *
	 * @access protected
	 */
	function get_attribs() {
		$str = parent::get_attribs();
		if ($this->action != '')  		{ $str .= ' action="'.$this->action.'"'; }
		if ($this->enctype != '')  		{ $str .= ' enctype="'.$this->enctype.'"'; }
		if ($this->method != '')  		{ $str .= ' method="'.$this->method.'"'; }
		if ($this->name != '')  		{ $str .= ' name="'.$this->name.'"'; }
		if ($this->target != '')  		{ $str .= ' target="'.$this->target.'"'; }
		return $str;
	}

	/**
	 * Get html element as string
	 *
	 * @access public
	 * @return string
	 */
	function get_string() {
		$str = '';
		$arr = $this->get_object();
		foreach($arr as $value) {
			if(is_object($value)) {
				$str .= $value->get_string();
			} else {
				$str .= $value;
			}
		}
		$attribs = $this->get_attribs();
		$_str  = '';
		$_str .= "\n<form$attribs>\n";
		$_str .= $str;
		$_str .= "\n</form>\n";
		return $_str;
	}

	function add($object, $key = null) {
		$this->elements[$key] = $object;
	}

	//---------------------------------------
	/**
	 * get array for htmlobject_template
	 *
	 * return array($this->elements[$key] => htmlobject)
	 *
	 * @access public
	 * @return array of objects
	 */
	//---------------------------------------
	function get_object() {
		$a = array();
    	$k = array_keys($this->elements);
    	$c = count($k);
		reset($this->elements);
		for($i = 0; $i < $c; ++$i) {
			$v = $this->elements[$k[$i]];
			if(is_object($v)){	
				if(
					$v instanceof htmlobject_formbuilder ||
					$v instanceof htmlobject_formbuilder_debug
				) {	
					$params = $v->get_object();
					foreach($params as $key1 => $value1) {
						$a[$key1] = $value1;
					}
				} else {
					$a[$k[$i]] = $v;
				}
			}
		}
		return $a;
	}

	//---------------------------------------
	/**
	 * get grouped array for htmlobject_template
	 * 
	 * 
	 * return array($this->elements[$key] => htmlobject,
	 * 				param => array(htmlobject))
	 *
	 * @access public
	 * @param params array(param => expression)
	 * @return array of objects
	 */
	//---------------------------------------
	function group_object( $params, $tostring = false ) {

		$obj = $this->get_object();
		$a   = array();
		$k   = array_keys($obj);
		$c   = count($k);
		reset($obj);
		for($i = 0; $i < $c; ++$i) {
			foreach($params as $param => $replace) {
				if( strpos($k[$i], $replace) !== false ) {
					$v = $obj[$k[$i]];
					if($tostring === true) { $v = $v->get_string(); }
					$a[$param][$k[$i]] = $v;
					unset($obj[$k[$i]]);
				}
			}
		}

		$k = array_keys($obj);
		$c = count($k);
		reset($obj);
		for($i = 0; $i < $c; ++$i) {
			$v = $obj[$k[$i]];
			if($tostring === true) { $v = $v->get_string(); }
			$a[$k[$i]] = $v;
		}

		return $a;
	}

}
?>
