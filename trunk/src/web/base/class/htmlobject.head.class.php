<?php
/*
  This file is part of openQRM.

    openQRM is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2
    as published by the Free Software Foundation.

    openQRM is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with openQRM.  If not, see <http://www.gnu.org/licenses/>.

    Copyright 2009, Matthias Rechenburg <matt@openqrm.com>
*/
class htmlobject_doctype
{
/**
* Doctype
* @access public
* @var string ['html', 'xhtml']
*/
var $doctype = 'html';
/**
* Doctypemodel
* @access public
* @var string ['strict', 'transitional', 'frameset']
*/
var $doctypemodel = 'transitional';

function get_doctype() {

$this->doctype = strtolower($this->doctype);
$this->doctypemodel = strtolower($this->doctypemodel);

if($this->doctype == 'xhtml' && $this->doctypemodel == 'strict') {
$_strReturn = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
'; }

if($this->doctype == 'xhtml' && $this->doctypemodel == 'transitional') {
$_strReturn = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
'; }

if($this->doctype == 'xhtml' && $this->doctypemodel == 'frameset') {
$_strReturn = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
'; }

if($this->doctype == 'html' && $this->doctypemodel == 'strict') {
$_strReturn = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html>
'; }

if($this->doctype == 'html' && $this->doctypemodel == 'transitional') {
$_strReturn = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
'; }

if($this->doctype == 'html' && $this->doctypemodel == 'frameset') {
$_strReturn = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
	"http://www.w3.org/TR/html4/frameset.dtd">
<html>
'; }

return $_strReturn;
}


}


class htmlobject_head extends htmlobject_doctype
{
/**
* Title of page
* @access public
* @var string
*/
var $title = '';

/**
* internal use only
*/
var $_meta = array();
var $_style = array();
var $_script = array();

	/**
	* get head values as string
	* @access public
	* @return string
	*/
	function get_string() {
	
		if(count($this->_style) > 0) {
			$this->add_meta('Content-Style-Type', 'text/css');
		}
		if(count($this->_script) > 0) {
			$this->add_meta('Content-Script-Type', 'text/javascript');
		}
		$_strReturn = "\n";
		$_strReturn .= $this->get_doctype();
		$_strReturn .= "<head>\n";
		$_strReturn .= implode("\n", $this->_meta)."\n";
		$_strReturn .= implode("\n", $this->_style)."\n";
		$_strReturn .= implode("\n", $this->_script)."\n";
		$_strReturn .= '<title>'.$this->title."</title>\n";
		$_strReturn .= "</head>\n";

		return $_strReturn;
	}
	/**
	* Add metatag to head
	* @access public
	* @param $value string
	* @param $content string
	* @param $type string [http-equiv, name]	
	*/	
	function add_meta ($value, $content ,$type = 'http-equiv') {
		$this->_meta[] = '<meta '.$type.'="'.$value.'" content="'.$content.'">';
	}
	/**
	* Add external stylesheet to head
	* @access public
	* @param $path string [url]
	* @param $media string [all, screen, print]
	*/
	function add_style ($path, $media='all') {
		$this->_style[] = '<link rel="stylesheet" media="'.$media.'" href="'.$path.'">';
	}
	/**
	* Add external script to head
	* @access public
	* @param $path string [url]
	*/	
	function add_script ($path) {
		$this->_script[] = '<script src="'.$path.'" type="text/javascript"></script>';
	}
}



?>