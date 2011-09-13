<?php
/**
 * @package htmlobjects
 *
 */

 /**
 * htmlobject_template
 *
 * @package htmlobjects
 * @author Alexander Kuballa <akuballa@users.sourceforge.net>
 * @copyright Copyright (c) 2009, Alexander Kuballa
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0
 */

class htmlobject_template
{

/**
* $file[handle] = "filename";
* @var array
*/
var $file = '';

/*
* $_keys[key] = "key"
* @var array
*/
var $_keys = array();
    
/**
* $elements[key] = "value";
* @var array
*/
var $elements = array();

	/**
	* Constructor
	*
	* @access public
	*/
	function htmlobject_template($file) {
		$this->file = $file;
	}

    /**
     * Set corresponding substitutions for placeholders
     *
     * @access public
     * @param  string name of a variable that is to be defined or an array of variables with value substitution as key/value pairs
     * @param  string value of that variable
     * @param  boolean if true, the value is appended to the variable's existing value
     */
	function init($varname, $value = "", $append = false) {
		$this->_keys['?']    = $this->_varname('?');
		$this->elements['?'] = '';
        if (!is_array($varname)) {
			$varname = array($varname);
		}
        reset($varname);
        while (list($k, $v) = each($varname)) {
			if ($k !== '') {
				$this->_keys[$k]   = $this->_varname($k);
				($append) ? $this->elements[$k] .= $v : $this->elements[$k] = $v;
				$this->elements['?'] .= '&#123;'.$k.'&#125;';
			}
		}
	}

    /**
     * parse variables into file
     *
     * @access private
     * @return string
     */
    
    function _parse() {
        $file    = $this->_get_file();
        $search  = array();
        $replace = array();
        $i       = 0;
        foreach($this->_keys as $key => $value) {
            $search[$i]  = $value;
			$replace[$i] = '';
            $element     = $this->elements[$key];
			if(!is_array($element)) {
				$element = array($element);
			}
			foreach($element as $value) {
		        if(is_object($value)) {
		            $replace[$i] .= $value->get_string();
		        } else {
					$replace[$i] .= $value;
				}
			}
            ++$i;
        }
        return str_replace($search, $replace, $file);
    }
    
    /**
     * Get Template as string
     *
     * @access public
     * @return string parsed file
     */

    function get_string() {
        return $this->_parse();
    }

    /**
     * Protect a replacement variable
     *
     * @access private
     * @param  string name of replacement variable
     * @return string replaced variable
     */
	function _varname($varname) {
		return "{".$varname."}";
	}

    /**
     * get template file
     *
     * @access private
     * @return mixed FALSE if error, string if ok
     */

    function _get_file() {
		$str = '';
		$filename = $this->file;
        if (!file_exists($filename)) {
            $this->_halt(sprintf("filename: file %s does not exist.",$filename));
            return false;
        }
        if (function_exists("file_get_contents")) {
            $str = @file_get_contents($filename);
        } else {
            if (!$fp = @fopen($filename,"r")) {
                $this->_halt("loadfile: couldn't open $filename");
                return false;
            }
            $str = @fread($fp,filesize($filename));
            @fclose($fp);
        }
        if ($str === '') {
            $this->_halt("_get_file: While loading $filename does not exist or is empty.");
            return false;
        }
        return $str;
    }

	//-------------------------------------------------
	//  HELPERS
	//-------------------------------------------------

    /**
     * rtrim string
     *
     * @access public
     * @param  string string to rtrim
     * @return rtrimed, i.e. substituted string
     */
	function rtrim($str) {
		$str = preg_replace('/{[^ \t\r\n}]+}/', "", $str);
		return $str;
	} 

    /**
     * Error function. _halt template system with message to show
     *
     * @access public
     * @param  string message to show
     * @return bool
     */

    function _halt($msg) {
        printf("<b>Template Error:</b> %s<br>\n", $msg);
        return false;
    }


}
?>
