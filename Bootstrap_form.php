<?php if(! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Bootstrap Form Builder Library for CodeIgniter
 *
 * New BSD License
 * ===============
 * 
 * Copyright (c) 2013, Sha Alibhai
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * * Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * * Neither the names of the copyright holders nor the names of its
 *   contributors may be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author		Sha Alibhai
 * @link		http://github.com/shalotelli
 * @version 	v1.0.0
 *
 * @todo Add help span
 * @todo Add validation
 * @todo Add alert labels
 * @todo Add button addons
 */
class Bootstrap_form {
	/**
	 * CodeIgniter instance
	 * @var Object
	 */
	private $_ci;

	/**
	 * Default constructor
	 */
	public function __construct() {
		// get CodeIgniter instance
		$this->_ci =& get_instance();
	}

	/**
	 * Create opening form tag string
	 * 
	 * @param  string $action Form action
	 * @param  array  $attr   Key => Value array of form attributes (id, class etc)
	 * @param  array  $hidden Key => Value array of hidden fields
	 * @return String 		  Opening form tag
	 */
	public function open_form($action='', $attr='', $hidden=array()) {
		// if no attr set, default to POST
		if($attr==='') {
			$attr = 'method="POST"';
		}

		// if an action is not a full URL then turn it into one
		if($action && ! strpos($action, '://')) {
			$action = $this->_ci->config->site_url($action);
		}

		// if no action is provided then set to the current url
		$action OR $action = $this->_ci->config->site_url($this->_ci->uri->uri_string());

		// generate output
		$output  = "<form action=\"".$action."\" ";
		$output .= $this->attr_to_str($attr, true);
		$output .= ">\n";

		// Add CSRF field if enabled, but leave it out for GET requests and requests to external websites
		if($this->_ci->config->item('csfr_protection') && !(! strpos($action, $this->_ci->config->base_url()) or strpos($output, 'method="GET"'))) {
			$hidden[$this->_ci->get_csfr_token_name()] = $this->_ci->security->get_csfr_hash();
		}

		// generate any hidden fields
		if(is_array($hidden) && count($hidden)>0) {
			$output .= "<div style=\"display:none;\">\n";
			$output .= $this->form_hidden($hidden);
			$output .= "\n</div>\n";
		}

		return $output;
	}

	/**
	 * Create form with multipart encoding
	 * 
	 * @param  string $action Form action
	 * @param  array  $attr   Key => Value array of form attributes (id, class etc)
	 * @param  array  $hidden Key => Value array of hidden fields
	 * @return String 		  Opening form tag with multipart encoding
	 */
	public function open_multipart_form($action='', $attr='', $hidden=array()) {
		$enctype = " enctype=\"multipart/form-data\"";

		// add enctype if not already set
		if(is_string($attr)) {
			$attr .= $enctype;
		} else {
			$attr['enctype'] = $enctype; 
		}

		// create form tag
		return $this->open_form($action, $attr, $hidden);
	}

	/**
	 * Closing form tag
	 * 
	 * @return String Closing form tag
	 */
	public function close_form() {
		return "</form>\n";
	}

	/**
	 * Group elements in fieldset
	 * 
	 * @param  string $legend Legend Text
	 * @param  array  $attr   Key => Value array of fieldset attributes (id, class etc)
	 * @return String         Opening fieldset tag
	 */
	public function open_fieldset($legend='', $attr=array()) {
		$output  = "<fieldset";
		$output .= $this->attr_to_str($attr);
		$output .= ">\n";

		if($legend!=='') {
			$output .= "<legend>".$legend."</legend>\n";
		}

		return $output;
	}

	/**
	 * Closing fieldset tag
	 * 
	 * @return String Closing fieldset tag
	 */
	public function close_fieldset() {
		return "</fieldset>\n";
	}

	/**
	 * Hidden input field
	 * 
	 * @param  array $name   Array of Key => Value or string of name
	 * @param  array $value  Array of Key => Value or string of values
	 * @return String        Hidden field tag
	 */
	public function hidden($name, $value='') {
		// if array given, recurse through elements
		if(is_array($name)) {
			foreach($name as $key => $val) {
				$this->hidden($key, $val);
			}
		}

		// if value is not an array create hidden input
		if(! is_array($value)) {
			$attr = array(
				'type' 	=> 'hidden',
				'name' 	=> $name,
				'id'   	=> $id,
				'value' => $value
			);

			$output = $this->create_input($attr);

			return $output;
		} else {
			// input is an attribute array, recurse
			foreach($value as $key => $val) {
				$key = (is_int($key)) ? '' : $key;
				$this->hidden($name,'['.$key.']', $val);
			}
		}
	}

	/**
	 * Generate input[type="text"]
	 * 
	 * @param  array $attr    [description]
	 * @param  string $value   [description]
	 * @param  string $prepend [description]
	 * @param  string $append  [description]
	 * @return [type]          [description]
	 */
	public function text($attr='', $value='', $prepend='', $append='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type'  => 'text',
			'id'    => (!is_array($attr)) ? $attr : '',
			'name'  => (!is_array($attr)) ? $attr : '',
			'value' => $this->clean_str($value)
		);

		// merge & stringify
		$attributes = $this->parse_form_attr($attr, $defaults);

		// get id & name vals depending on what exists
		if(! is_array($attr)) {
			$id = $attr;
			$name = $attr;
		} elseif(is_array($attr) && isset($attr['id']) && isset($attr['name'])) {
			$id = $attr['id'];
			$name = $attr['name'];
		} elseif(is_array($attr) && isset($attr['id'])) {
			$id = $attr['id'];
			$name = '';
		} elseif(is_array($attr) && isset($attr['name'])) {
			$id = $attr['name'];
			$name = $attr['name'];
		} else {
			$id = '';
			$name = '';
		}

		// create output
		$output .= $this->wrapper_top($id, $name);
		$output .= $this->build_input($attributes, $prepend, $append);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * Generate input[type="password"]
	 * 
	 * @param  string $attr    [description]
	 * @param  string $value   [description]
	 * @param  string $prepend [description]
	 * @param  string $append  [description]
	 * @return [type]          [description]
	 */
	public function password($attr='', $value='', $prepend='', $append='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type'  => 'password',
			'id'    => (!is_array($attr)) ? $attr : '',
			'name'  => (!is_array($attr)) ? $attr : '',
			'value' => $this->clean_str($value)
		);

		// merge & stringify
		$attributes = $this->parse_form_attr($attr, $defaults);

		// get id & name vals depending on what exists
		if(! is_array($attr)) {
			$id = $attr;
			$name = $attr;
		} elseif(is_array($attr) && isset($attr['id']) && isset($attr['name'])) {
			$id = $attr['id'];
			$name = $attr['name'];
		} elseif(is_array($attr) && isset($attr['id'])) {
			$id = $attr['id'];
			$name = '';
		} elseif(is_array($attr) && isset($attr['name'])) {
			$id = $attr['name'];
			$name = $attr['name'];
		} else {
			$id = '';
			$name = '';
		}

		// create output
		$output .= $this->wrapper_top($id, $name);
		$output .= $this->build_input($attributes, $prepend, $append);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * Generate input[type="file"]
	 * 
	 * @param  string $attr  [description]
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	public function upload($attr='', $value='') {
		// output buffer
		$output = '';

		// if attr is a string, assume its just the name
		if(! is_array($attr)) {
			$attr = array('name' => $attr);
		}

		// set type
		$attr['type'] = 'file';

		// get id & name vals depending on what exists
		if(! is_array($attr)) {
			$id = $attr;
			$name = $attr;
		} elseif(is_array($attr) && isset($attr['id']) && isset($attr['name'])) {
			$id = $attr['id'];
			$name = $attr['name'];
		} elseif(is_array($attr) && isset($attr['id'])) {
			$id = $attr['id'];
			$name = '';
		} elseif(is_array($attr) && isset($attr['name'])) {
			$id = $attr['name'];
			$name = $attr['name'];
		} else {
			$id = '';
			$name = '';
		}

		// create output
		$output .= $this->wrapper_top($id, $name);
		$output .= $this->build_input();
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * Generate input[type="checkbox"]
	 * 
	 * @param  string  $attr    [description]
	 * @param  string  $value   [description]
	 * @param  string  $label   [description]
	 * @param  boolean $checked [description]
	 * @return [type]           [description]
	 */
	public function checkbox($attr='', $value='', $label='', $checked=false) {
		// output buffer
		$output = '';

		// if attr is a string, assume its just the name
		if(! is_array($attr)) {
			$attr = array('name' => $attr);
		}

		// create output
		$output .= $this->wrapper_top();
		$output .= $this->build_option($attr, $value, $label, $checked);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * Generate input[type="radio"]
	 * 
	 * @param  string  $attr    [description]
	 * @param  string  $value   [description]
	 * @param  string  $label   [description]
	 * @param  boolean $checked [description]
	 * @return [type]           [description]
	 */
	public function radio($attr='', $value='', $label='', $checked=false) {
		// output buffer
		$output = '';

		// if attr is a string, assume its just the name
		if(! is_array($attr)) {
			$attr = array('name' => $attr);
		}

		// set type
		$attr['type'] = 'radio';

		// create output
		$output .= $this->wrapper_top();
		$output .= $this->build_option($attr, $value, $label, $checked);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [dropdown description]
	 * 
	 * @param  string $name     [description]
	 * @param  array  $options  [description]
	 * @param  array  $selected [description]
	 * @return [type]           [description]
	 */
	public function dropdown($name='', $options=array(), $selected=array(), $attr='') {
		// output buffer
		$output = '';

		// convert to array for standardized processing
		if(! is_array($selected)) {
			$selected = array($selected);
		}

		// if no selected given, check post and set it through there if it exists
		if(count($selected)===0) {
			if($this->_ci->input->post($name)) {
				$selected = $this->_ci->input->post($name);
			}
		}

		// prepend space
		if($attr!=='') {
			$attr = ' '.$attr;
		}

		// if more than one option selected, convert to multiselect
		$multiple = (count($selected)>1) ? 'multiple="multiple"' : '';

		// start ouput
		$output .= $this->wrapper_top($name, $name);
		$output .= "<select name=\"".$name."\" ".$attr.$multiple.">\n";

		// loop through options
		foreach($options as $key => $val) {
			// make sure key is a string
			$key = (string) $key;

			if(is_array($val) && ! empty($val)) {
				$output .= "<optgroup label=\"".$key."\">\n";

				foreach($val as $optgroup_key => $optgroup_val) {
					$sel = (in_array($optgroup_key, $selected)) ? 'selected="selected
					"' : '';

					$output .= "<option value=\"".$optgroup_key."\" ".$sel.">";
					$output .= (string) $optgroup_val;
					$output .= "</option>\n";
				}

				$output .= "</optgroup>\n";
			} else {
				$sel = (in_array($key, $selected)) ? ' selected="selected"' : '';

				$output .= "<option value=\"".$key."\" ".$sel.">";
				$output .= (string) $val;
				$output .= "</option>\n";
			}
		}

		// end output
		$output .= "</select>\n";
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [multiselect description]
	 * 
	 * @param  string $name     [description]
	 * @param  array  $options  [description]
	 * @param  array  $selected [description]
	 * @param  string $attr     [description]
	 * @return [type]           [description]
	 */
	public function multiselect($name='', $options=array(), $selected=array(), $attr='') {
		if(! strpos($attr, 'multiple')) {
			$attr .= 'multiple="multiple"';
		}

		return $this->dropdown($name, $options, $selected, $attr);
	}

	/**
	 * [textarea description]
	 * 
	 * @param  string $attr  [description]
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	public function textarea($attr='', $value='') {
		$output = '';
		$defaults = array(
			'name' => (! is_array($attr)) ? $attr : '',
			'cols' => 40,
			'rows' => 10
		);

		if(! is_array($attr) || ! isset($attr['value'])) {
			$val = $value;
		} else {
			$val = $attr['value'];
			unset($attr['value']);
		}

		if(!is_array($attr)) {
			$id = $attr;
			$name = $attr;
		} elseif(is_array($attr) && isset($attr['id']) && isset($attr['name'])) {
			$id = $attr['id'];
			$name = $attr['name'];
		} elseif(is_array($attr) && isset($attr['id'])) {
			$id = $attr['id'];
			$name = '';
		} elseif(is_array($attr) && isset($attr['name'])) {
			$id = $attr['name'];
			$name = $attr['name'];
		} else {
			$id = '';
			$name = '';
		}

		$output .= $this->wrapper_top($id, $name);
		$output .= "<textarea ";
		$output .= $this->parse_from_attr($attr, $defaults);
		$output .= ">";
		$output .= $this->clean_str($val);
		$output .= "</textarea>\n";
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [button description]
	 * 
	 * @param  array  $attr  [description]
	 * @param  string $label [description]
	 * @param  string $value [description]
	 * @param  string $style [description]
	 * @param  string $icon  [description]
	 * @return [type]        [description]
	 */
	public function button($attr=array(), $label='', $value='', $style='', $icon='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type' 	=> 'button',
			'name' 	=> (! is_array($data)) ? $data : '',
			'value' => $value
		);

		if(is_array($attr) && isset($attr['class'])) {
			$attr['class'] .= ' btn '.$style;
		}

		$$attr_str = $this->parse_from_attr($attr, $defaults);

		$output .= $this->wrapper_top();
		$output .= $this->build_button($attr_str, $label, $icon);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [single_button description]
	 * 
	 * @param  array  $attr  [description]
	 * @param  string $label [description]
	 * @param  string $value [description]
	 * @param  string $style [description]
	 * @param  string $icon  [description]
	 * @return [type]        [description]
	 */
	public function single_button($attr=array(), $label='', $value='', $style='', $icon='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type' 	=> 'button',
			'name' 	=> (! is_array($data)) ? $data : '',
			'value' => $value
		);

		if(is_array($attr) && isset($attr['class'])) {
			$attr['class'] .= ' btn '.$style;
		}

		$$attr_str = $this->parse_from_attr($attr, $defaults);

		$output .= $this->build_button($attr_str, $label, $icon);

		return $output;
	}

	/**
	 * [submit description]
	 * 
	 * @param  array  $attr  [description]
	 * @param  string $label [description]
	 * @param  string $value [description]
	 * @param  string $style [description]
	 * @param  string $icon  [description]
	 * @return [type]        [description]
	 */
	public function submit($attr=array(), $label='', $value='', $style='', $icon='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type' 	=> 'submit',
			'name' 	=> (! is_array($data)) ? $data : '',
			'value' => $value
		);

		if(is_array($attr) && isset($attr['class'])) {
			$attr['class'] .= ' btn '.$style;
		}

		$$attr_str = $this->parse_from_attr($attr, $defaults);

		$output .= $this->wrapper_top();
		$output .= $this->build_button($attr_str, $label, $icon);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [reset description]
	 * 
	 * @param  array  $attr  [description]
	 * @param  string $label [description]
	 * @param  string $value [description]
	 * @param  string $style [description]
	 * @param  string $icon  [description]
	 * @return [type]        [description]
	 */
	public function reset($attr=array(), $label='', $value='', $style='', $icon='') {
		// output buffer
		$output = '';

		// set default options
		$defaults = array(
			'type' 	=> 'reset',
			'name' 	=> (! is_array($data)) ? $data : '',
			'value' => $value
		);

		if(is_array($attr) && isset($attr['class'])) {
			$attr['class'] .= ' btn '.$style;
		}

		$$attr_str = $this->parse_from_attr($attr, $defaults);

		$output .= $this->wrapper_top();
		$output .= $this->build_button($attr_str, $label, $icon);
		$output .= $this->wrapper_bottom();

		return $output;
	}

	/**
	 * [build_input description]
	 * 
	 * @param  [type] $attr    [description]
	 * @param  string $prepend [description]
	 * @param  string $append  [description]
	 * @return [type]          [description]
	 */
	private function build_input($attr, $prepend='', $append='') {
		$output  = $this->addon_top($prepend, $append);
		$output .= "<input ".$attr.">";
		$output .= $this->addon_bottom($prepend, $append);

		return $output;
	}

	/**
	 * [build_option description]
	 * 
	 * @param  [type]  $attr    [description]
	 * @param  string  $value   [description]
	 * @param  string  $label   [description]
	 * @param  boolean $checked [description]
	 * @return [type]           [description]
	 */
	private function build_option($attr, $value='', $label='', $checked=false) {
		// output buffer
		$output = '';

		// default type is checkbox (just because..)
		$type = 'checkbox';

		// set default options
		$defaults = array(
			'type' 	=> $type,
			'name' 	=> ((! is_array($attr)) ? $attr : ''),
			'value' => $value
		);

		if(is_array($attr)) {
			if(array_key_exists('checked', $attr)) {
				$checked = $attr['checked'];
				
				if(! $checked) {
					unset($attr['checked']);
				} else {
					$attr['checked'] = 'checked';
				}
			}

			if(array_key_exists('type', $attr)) {
				$type = $attr['type'];
			}
		}

		if($checked) {
			$defaults['checked'] = 'checked';
		} else {
			unset($defaults['checked']);
		}

		$attr_str = $this->parse_from_attr($attr, $defaults);

		$output .= "<label class=\"".$type."\">\n";
		$output .= $this->create_input($attr_str);
		$output .= ($label!=='') ? $label."\n" : "\n";
		$output .= "<label>\n";

		return $output;
	}

	/**
	 * [build_button description]
	 * 
	 * @param  [type] $attr  [description]
	 * @param  [type] $label [description]
	 * @param  [type] $icon  [description]
	 * @return [type]        [description]
	 */
	private function build_button($attr, $label, $icon) {
		// add icon if set
		$icon = ($icon!=='') ? "<i class=\"".$icon."\"></i>" : '';

		return "<button".$attr.">".$icon." ".$label."</button>\n";
	}

	/**
	 * Open control-group
	 * 
	 * @param  string $id    Element ID
	 * @param  string $label Element label
	 * @return string        Opening control group
	 */
	private function wrapper_top($id='', $label='') {
		$wrapper = "<div class=\"control-group\">\n";

		// if label exists, show it
		if($label!=='') {
			// clean up label
			$label = ucfirst(str_replace('_', ' ', $label));

			$wrapper .= "<label class=\"control-label\" for=\"".$id."\">".$label."</label>\n";
		}
				
		$wrapper .= "<div class=\"controls\">\n";

		return $wrapper;
	}

	/**
	 * Close control-group
	 * 
	 * @return string Closed control group
	 */
	private function wrapper_bottom() {
		$wrapper = "	<div>\n
					</div>";

		return $wrapper;
	}

	/**
	 * [addon_top description]
	 * 
	 * @param  [type] $prepend [description]
	 * @param  [type] $append  [description]
	 * @return [type]          [description]
	 */
	private function addon_top($prepend, $append) {
		$class = '';
		$addon = ''; 

		if($prepend!=='' || $append!=='') {
			$class .= ($prepend!=='') ? 'input-prepend ' : '';
			$class .= ($append!=='')  ? 'input-append ' : '';

			$addon .= "<div class=\"".$class."\">\n";

			if($prepend!=='') {
				$addon .= "<span class=\"add-on\">".$prepend."</span>\n";
			}
		}

		return $addon;
	}

	/**
	 * [addon_bottom description]
	 * 
	 * @param  [type] $prepend [description]
	 * @param  [type] $append  [description]
	 * @return [type]          [description]
	 */
	private function addon_bottom($prepend, $append) {
		$addon = '';

		if($prepend!=='' || $append!=='') {
			if($append!=='') {
				$addon .= "<span class=\"add-on\">".$append."</span>\n";
			}

			$addon .= "</div>\n";
		}

		return $addon;
	}

	/**
	 * [clean_str description]
	 * 
	 * @param  string $str [description]
	 * @return [type]      [description]
	 */
	private function clean_str($str='') {
		// if array, recurse
		if(is_array($str)) {
			foreach($str as $key => $val) {
				$str[$key] = $this->clean_str($val);
			}

			return $str;
		}

		// give what you get
		if($str==='') {
			return '';
		}

		// encode special character (such as &, ", ', < & >)
		$str = htmlspecialchars($str);

		// replace anything thats missed
		$str = str_replace(array("'", '"'), array("&#39;", "&quot;"), $str);

		return $str;
	}

	/**
	 * [parse_form_attr description]
	 * 
	 * @param  [type] $attr     [description]
	 * @param  [type] $defaults [description]
	 * @return [type]           [description]
	 */
	private function parse_form_attr($attr, $defaults) {
		// if attr is array, overwrite defaults if they exists
		if(is_array($attr)) {
			foreach($defaults as $key => $val) {
				if(isset($attr[$key])) {
					$defaults[$key] = $attr[$key];
					unset($attr[$key]);
				}
			}

			// if theres anything left in attr, merge with defaults
			if(count($attr)>0) {
				$defaults = array_merge($defaults, $attr);
			}
		}

		// return string buffer
		$attr_str = '';

		foreach($defaults as $key => $val) {
			// clean any values
			if($key=='value') {
				$val = $this->clean_str($val);
			}

			// stringify
			$attr_str .= $key.'="'.$val.'" ';
		}

		return $attr_str;
	}

	/**
	 * Convert Key => Value array to string
	 * Also do some processing if the attr is for <form> element
	 * 
	 * @param  [type]  $attr    [description]
	 * @param  boolean $is_form [description]
	 * @return [type]           [description]
	 */
	private function attr_to_str($attr, $is_form=false) {
		// if string given, do some processing
		if(is_string($attr) && strlen($attr)>0) {
			// add method if not present
			if($is_form && !strpos($attr, 'method=')) {
				$attr .= ' method="POST"';
			}

			// add charset if not present
			if($is_form && !strpos($attr, 'accept-charset=')) {
				$attr .= ' accept-charset="'.strtolower(config_item('charset')).'"';
			}

			return $attr;
		}

		// object -> array
		if(is_object($attr) && count($attr)>0) {
			$attr = (array) $attr;
		}

		if(is_array($attr) && count($attr)>0) {
			// string buffer
			$attr_str = '';

			// add method if not present
			if($is_form && !isset($attr['method'])) {
				$attr_str .= ' method="POST"';
			}

			// add charset if not present
			if($is_form && !isset($attr['accept-charset'])) {
				$attr .= ' accept-charset="'.strtolower(config_item('charset')).'"';
			}

			// convery each element to string
			foreach($attr as $key => $val) {
				$attr_str .= ' '.$key.'="'.$val.'"';
			}
		}

		return $attr_str;
	}

}