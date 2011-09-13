<?php
/**
 * @package htmlobjects
 *
 */

/**
 * Formbuilder
 * uses class htmlobject_input, htmlobject_button
 *
 * @package htmlobjects
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2008, Alexander Kuballa
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
  */

class htmlobject_formbuilder extends htmlobject_http
{
/**
* store data
* @access protected
* @var string
*/
var $data = array();
/**
* store request
* @access protected
* @var array
*/
var $request = array();
/**
* store errors
* @access protected
* @var array|null
*/
var $request_errors = null;
/**
* translation 
* @access public
* @var array
*/
var $lang = array(
	'error_required' => 'must not be empty',
	'required'       => '*',
);


	//---------------------------------------
	/**
	 * Constructor
	 *
	 * Good to know:
	 * If form is cleared (inputs are empty) and then submitted,
	 * form will load preset values and will not appear empty.
	 * No Errors will be displayed. Form will appear like opening
	 * it for the first time.
	 *
	 * <code>
	 *
	 * $data  = array();
	 * $data['name']                              = array ();
	 * $data['name']['label']                     = 'Name';
	 * $data['name']['required']                  = true;
	 * $data['name']['static']                    = false;
	 * // validation
	 * $data['name']['validate']                  = array ();
	 * $data['name']['validate']['regex']         = '/^[a-z0-9~._-]+$/i';
	 * $data['name']['validate']['errormsg']      = 'string must be a-z0-9~._-';
	 * // build object
	 * $data['name']['object']                    = array ();
	 * $data['name']['object']['type']            = 'htmlobject_input';
	 * $data['name']['object']['attrib']          = array();
	 * $data['name']['object']['attrib']['type']  = 'text';
	 * $data['name']['object']['attrib']['name']  = 'name';
	 * $data['name']['object']['attrib']['value'] = 'somevalue';
	 *
	 * $formbuilder = new htmlobject_formbuilder( $data );
	 *
	 * // Actions
	 * // no errors, do something
	 * if(!$formbuilder->get_errors()) {
	 *		$values = $formbuilder->get_request_as_array();
	 *		print_r($values);
	 * }
	 *
	 * $template = new Template_PHPLIB();
	 * $template->debug = 0;
	 * $template->setFile('t', 'html/template.html');
	 * $template->setVar($formbuilder->get_template_array());
	 *
	 * echo $template->parse('out', 't');
	 *
	 * </code>
	 * @access public
	 * @param object $htmlobject
	 */
	//---------------------------------------
	function htmlobject_formbuilder( $htmlobject ) {
		$this->html = $htmlobject;
	}

	/**
	 * Init Formbuilder
	 *
	 * @access public
	 * @param object $htmlobject
	 */
	function init( $data ) {
		// filter quots (")
		$this->set_request_filter(array(
				array( 'pattern' => '~\r\n~', 'replace' => '\n'),
				array( 'pattern' => '~&lt;~', 'replace' => '<'),
				array( 'pattern' => '~&quot;~', 'replace' => '"'),
			));
		$this->data = $data;
		$this->_set_request();
		$this->_set_request_errors();
		$this->_set_elements($data);
	}

	//---------------------------------------
	/**
	 * get request values as array
     *
	 * @access public
	 * @return array
	 */
	//---------------------------------------
	function get_request_as_array() {
		return $this->request;
	}

	//---------------------------------------
	/**
	 * get errors
	 *
	 * will return array('name' => 'errormsg', ...)
	 * or null if no error occured
	 *
	 * @access public
	 * @return array|null
	 */
	//---------------------------------------
	function get_errors() {
		return $this->request_errors;
	}

	//---------------------------------------
	/**
	 * set values from http request as array
	 *
	 * @access protected
	 */
	//---------------------------------------
	function _set_request() {

		$arReturn = null;
		foreach ($this->data as $data) {
			if(isset($data['object']['attrib']['name'])) {
				if( !isset($data['static']) || $data['static'] !== true ) {
					// set vars
					$name    = $this->unindex_array($data['object']['attrib']['name']);
					$request = $this->get_request($name);
					if($request) {
						$regex = '~\[(.[^\]]*)\]~';
						preg_match_all($regex, $name, $matches, PREG_SET_ORDER);
						if($matches) {
							$tag   = preg_replace('~\[.*\]~', '', $name);
							$count = count($matches)-1;
							$ar    = &$arReturn[$tag];							
							for($i = 0; $i <= $count; ++$i){
								$ar = &$ar[$matches[$i][1]];
								if($i === $count){
									$ar = $request;
								}
							}
						} else {
							$arReturn[$name] = $request;
						}
					}
				}
			}
		}
		$this->request = $arReturn;

	}

	//---------------------------------------
	/**
	 * Check $this->data request
	 *
	 * Returns array of errors if
	 * request does not match given regex.
	 * Empty if no missmatch occured.
	 *
	 * @access protected
	 * @todo pregmatch for arrays
	 */
	//---------------------------------------
	function _set_request_errors() {
		foreach ($this->data as $data) {
			// handle validate
			if(
				isset($data['validate']) &&
				isset($data['validate']['regex']) &&
				isset($data['validate']['errormsg']) &&
				isset($data['object']['attrib']['name']) &&
				count($this->request) > 0
			) {
				$regex   = $data['validate']['regex'];
				$name    = $data['object']['attrib']['name'];
				$request = '$this->request'.$this->string_to_index($name);
				if(eval("return isset($request);") && isset($regex) && $regex != '') {
					$matches = @preg_match($regex, eval("return $request;"));
					if(!$matches) {
						$this->request_errors[$name] = $data['validate']['errormsg'];
					}
				}
			}
			// handle required
			if(
				isset($data['object']['attrib']['name']) &&
				count($this->request) > 0 &&
				isset($data['required'])
			) {
				$name    = $data['object']['attrib']['name'];
				$request = '$this->request'.$this->string_to_index($name);
				if (eval("return !isset($request);") && isset($data['required']) && $data['required'] == true) {
					$this->request_errors[$name] = $data['label'].' '.$this->lang['error_required'];
				}
			}
		}

	}

	//---------------------------------------
	/**
	 * set elements
	 * make sure data, request and request_errors
	 * are set first
	 *
	 * @access protected
	 */
	//---------------------------------------
	function _set_elements($data) {
		$ar = array();
		foreach($data as $key => $value) {
			$obj = str_replace('htmlobject_', '', $value['object']['type']);
			$obj = $this->html->$obj();
			foreach($value['object']['attrib'] as $akey => $attrib) {
				$obj->$akey = $attrib;
			}
			$this->elements[$key] = $obj;
			$this->_set_elements_value($key);			
		}
	}

	//---------------------------------------
	/**
	 * set elements value
	 * make sure data, request and request_errors
	 * are set first
	 *
	 * @access protected
	 */
	//---------------------------------------
	function _set_elements_value($key) {
		$name = $this->data[$key]['object']['attrib']['name'];
		if( isset($this->data[$key]['static']) && $this->data[$key]['static'] === true ) {
			$this->handle_htmlobject($key, $this->data[$key]['object']['attrib']['value']);
		} else {
			if(	isset($this->request) && count($this->request) > 0) {
				if(isset($this->request[$name])) {
					$this->handle_htmlobject($key, $this->request[$name]);
				}
			}
		}		
	}

	//---------------------------------------
	/**
	 * handle htmlobject
	 *
	 * @access protected
	 * @param object $html
	 * @param string $request
	 * @return object
	 */
	//---------------------------------------
	function handle_htmlobject($key, $value) {
		$html = $this->elements[$key];
		if($html instanceof htmlobject_input) {
			$this->handle_htmlobject_input($key, $value);
		}
		if($html instanceof htmlobject_textarea) {
			$this->handle_htmlobject_textarea($key, $value);
		}
		if($html instanceof htmlobject_select) {
			$this->handle_htmlobject_select($key, $value);
		}
	}

	//---------------------------------------
	/**
	 * handle htmlobject_input
	 *
	 * @access protected
	 * @param object $html
	 * @param array $attrib
	 * @param string $request
	 * @return object
	 */
	//---------------------------------------
	function handle_htmlobject_input($key, $value) {

		$html = $this->elements[$key];

		$html->type = strtolower($html->type);
		switch($html->type) {
			case 'submit':
			case 'reset':
			case 'file':
			case 'image':
			case 'button':
				// do nothing
			break;
			case 'radio':
				if($value == $html->value) {
					$html->checked = true;
				} else {
					$html->checked = false;
				}
			break;
			case 'checkbox':
				$checked = false;
					if(is_string($value)) {
						if($value !== '') {
							$html->checked = true;
						}
					}
					if(is_array($value)) {
						if(in_array($html->value, $value)) {
							$html->checked = true;
						}
					}
			break;
			case 'text':
			case 'hidden':
			case 'password':
					$html->value = $value;
			break;
		}

	}

	//---------------------------------------
	/**
	 * handle htmlobject_select
	 *
	 * @access protected
	 * @param object $html
	 * @param string $request
	 * @return object
	 */
	//---------------------------------------
	function handle_htmlobject_select($key, $value) {
		$html = $this->elements[$key];
		if(is_array($value)) {
			$html->selected = $value;
		} else {
			$html->selected = array($value);
		}
	}

	//---------------------------------------
	/**
	 * handle htmlobject_textarea
	 *
	 * @access protected
	 * @param object $html
	 * @param string $request
	 * @return object
	 */
	//---------------------------------------
	function handle_htmlobject_textarea($key, $value) {
		$html = $this->elements[$key];
		$html->value = str_replace('<', '&lt;', eval("return $request;"));
	}

	//---------------------------------------
	/**
	 * handle label
	 *
	 * @access protected
	 * @param array $data
	 * @return string
	 */
	//---------------------------------------
	function get_label($data) {

		$label = '';
		if(
			isset($data['label']) && $data['label'] != '' &&
			isset($data['object']['attrib']['name'])
		) {
			$label = $data['label'];
			$name  = $data['object']['attrib']['name'];
			// mark error
			if($this->request_errors) {
				if(array_key_exists($name, $this->request_errors)) {
					$label = '<span class="error">'.$label.'</span>';
				}
			}
			// mark required
			if(isset($data['required']) && $data['required'] === true) {
				$label = $label.' '.$this->lang['required'];
			}
		}
		return $label;

	}

	//---------------------------------------
	/**
	 * get array for htmlobject_template
	 *
	 * will return array($name => htmlobject_box)
	 *
	 * @access public
	 * @param string name of element
	 * @return array of objects
	 */
	//---------------------------------------
	function get_object( $name = null ) {
		$a = array();
		if( $name ) {
			$data[$name] = $this->data[$name];
		} else {
			$data = $this->data;
		}
    	$k = array_keys($data);
    	$s = sizeOf($k);
		reset($data);
		for($i = 0; $i < $s; ++$i) {
			$html         = $this->elements[$k[$i]];
			$box          = $this->html->box();
			$box->label   = $this->get_label($data[$k[$i]]);
			$box->content = $html;
			if($box->label !== '') {
				$a = array_merge($a, array($k[$i] => $box));
			} else {
				$a = array_merge($a, array($k[$i] => $html));
			}
		}
		return $a;
	}

	//---------------------------------------
	/**
	 * get formbuilder as string
	 *
	 * @access public
	 * @return string
	 */
	//---------------------------------------
	function get_string( $name = null ) {

		$str = '';
		if( $name ) {
			$data = $data = $this->get_object( $name );
		} else {
			$data = $data = $this->get_object();
		}
		foreach( $data as $key => $value) {
			if( $name ) {
				if( $key === $name ) {
					$str .= $value->get_string();
				}				
			} else {
				$str .= $value->get_string();
			}
		}
		return $str;
	}


} // end class
?>
