<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Interfaces\TemplateInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;
use exface\Core\Exceptions\Configuration\ConfigOptionNotFoundError;
use exface\Core\CommonLogic\Model\Object;
use exface\Core\Interfaces\ExfaceClassInterface;
use exface\Core\CommonLogic\Translation;
use exface\Core\Interfaces\Widgets\iShowSingleAttribute;

abstract class AbstractJqueryElement implements ExfaceClassInterface {
	
	private $exf_widget = null;
	private $template = null;
	private $width_relative_unit = null;
	private $width_default = null;
	private $height_relative_unit = null;
	private $height_default = null;
	private $hint_max_chars_in_line = null;
	private $on_change_script = '';
	private $on_resize_script = '';
	private $ajax_url = null;
	private $function_prefix = null;
	private $id = null;
	private $element_type = null;
	
	/**
	 * Creates a template element for a given widget
	 * @param WidgetInterface $widget
	 * @param TemplateInterface $template
	 * @return void
	 */
	public function __construct(WidgetInterface $widget, TemplateInterface $template){
		$this->set_widget($widget);
		$this->template = $template;
		$template->register_element($this);
		$this->init();
	}
	
	/**
	 * This method is run every time the element is created. Override it to set inherited options.
	 * @return void
	 */
	protected function init(){
		
	}
	
	/**
	 * Returns the complete JS code needed for the element
	 */
	abstract public function generate_js();
	
	/**
	 * Returns the complete HTML code needed for the element
	 */
	abstract public function generate_html();
	
	/**
	 * Returns JavaScript headers, needed for the element as an array of lines.
	 * Make sure, it is always an array, as it is quite possible, that multiple elements
	 * require the same include and we will need to make sure, it is included only once.
	 * The array provides an easy way to get rid of identical lines.
	 *
	 * Note, that the main includes for the core of jEasyUI generally need to be
	 * placed in the template of the CMS. This method ensures, that widgets can
	 * add other includes like plugins, a plotting framework or other JS-resources.
	 * Thus, the abstract widget returns an empty array.
	 *
	 * @return string[]
	 */
	public function generate_headers(){
		$headers = array();
		if ($this->get_widget()->is_container()){
			foreach ($this->get_widget()->get_children() as $child){
				$headers = array_merge($headers, $this->get_template()->get_element($child)->generate_headers());
			}
		}
		return $headers;
	}
	
	/**
	 * Returns the widget, that this template element represents
	 * @return WidgetInterface
	 */
	public function get_widget() {
		return $this->exf_widget;
	}
	
	/**
	 * Sets the widget, represented by this element. Use with great caution! This method does not reinitialize the element. It is far
	 * safer to create a new element.
	 * 
	 * @param WidgetInterface $value
	 * @return AbstractJqueryElement
	 */
	protected function set_widget(WidgetInterface $value) {
		$this->exf_widget = $value;
		return $this;
	}
	
	/**
	 * Returns the template engine
	 * @return AbstractAjaxTemplate
	 */
	public function get_template(){
		return $this->template;
	}
	
	/**
	 * Returns the meta object of the widget, that this element represents.
	 * @return Object
	 */
	public function get_meta_object(){
		return $this->get_widget()->get_meta_object();
	}
	
	/**
	 * Returns the page id
	 * @return string
	 */
	public function get_page_id() {
		return $this->get_widget()->get_page()->get_id();
	}
	
	/**
	 * Returns the maximum number of characters in one line for hint messages in this template
	 * @return string
	 */
	protected function get_hint_max_chars_in_line() {
		if (is_null($this->hint_max_chars_in_line)){
			$this->hint_max_chars_in_line = $this->get_template()->get_config()->get_option('HINT_MAX_CHARS_IN_LINE');
		}
		return $this->hint_max_chars_in_line;
	}
	
	/**
	 * Returns a ready-to-use hint text, that will generally be included in float-overs for template elements
	 * @param unknown $hint_text
	 * @param string $remove_linebreaks
	 * @return string
	 */
	public function build_hint_text($hint_text = NULL, $remove_linebreaks = false){
		$max_hint_len = $this->get_hint_max_chars_in_line();
		$hint = $hint_text ? $hint_text : $this->get_widget()->get_hint();
		$hint = str_replace('"', '\"', $hint);
		if ($remove_linebreaks){
			$hint = trim(preg_replace('/\r|\n/', ' ', $hint));
		} else {
			$parts = explode("\n", $hint);
			$hint = '';
			foreach ($parts as $part){
				if (strlen($part) > $max_hint_len){
					$words = explode(' ', $part);
					$line = '';
					foreach ($words as $word){
						if (strlen($line)+strlen($word)+1 > $max_hint_len){
							$hint .= $line . "\n";
							$line = $word . ' ';
						} else {
							$line .= $word . ' ';
						}
					}
					$hint .= $line . "\n";
				} else {
					$hint .= $part . "\n";
				}
			}
		}
		$hint = trim($hint);
		return $hint;
	}
	
	/**
	 * Returns the default URL for AJAX requests by this element (relative to site root)
	 * @return string
	 */
	public function get_ajax_url() {
		if (is_null($this->ajax_url)){
			$this->ajax_url = $this->get_template()->get_config()->get_option('DEFAULT_AJAX_URL');
		}
		$request_id = $this->get_template()->get_workbench()->get_request_id();
		return $this->ajax_url . ($request_id ? '&exfrid=' . $request_id : '');
	}
	
	/**
	 * Changes the default URL for AJAX requests by this element (relative to site root)
	 * @param string $value
	 */
	public function set_ajax_url($value) {
		$this->ajax_url = $value;
	}
	
	/**
	 * Returns a unique prefix for JavaScript functions to be used with this element
	 * @return string
	 */
	public function build_js_function_prefix(){
		if (is_null($this->function_prefix)){
			$this->function_prefix = str_replace($this->get_template()->get_config()->get_option('FORBIDDEN_CHARS_IN_FUNCTION_PREFIX'), '_', $this->get_id()) . '_';
		}
		return $this->function_prefix;
	}
	
	/**
	 * Returns the type attribute of the resulting HTML-element. In pure HTML this is only usefull for elements like
	 * input fields (the type would be "text", "hidden", etc.), but many UI-frameworks use this kind of attribute
	 * to identify types of widgets. Returns NULL by default.
	 * @return string
	 */
	public function get_element_type() {
		return $this->element_type;
	}
	
	/**
	 * Sets the element type
	 * @param string $value
	 * @return AbstractJqueryElement
	 */
	public function set_element_type($value) {
		$this->element_type = $value;
		return $this;
	}
	
	/**
	 * Returns the id of the HTML-element representing the widget. Passing a widget id makes the method return the id of the element
	 * that belongs to that widget.
	 * 
	 * @return string
	 */
	public function get_id(){
		if (is_null($this->id)){
			$this->id = $this->clean_id($this->get_widget()->get_id()) . ($this->get_template()->get_workbench()->get_request_id() ? '_' . $this->get_template()->get_workbench()->get_request_id() : '');
		}
		return $this->id;
	}
	
	/**
	 * Replaces all characters, which are not supported in the ids of DOM-elements (i.e. "/" etc.)
	 * @param string $id
	 * @return string
	 */
	public function clean_id($id){
		return str_replace($this->get_template()->get_config()->get_option('FORBIDDEN_CHARS_IN_ELEMENT_ID'), '_', $id);
	}
	
	/**
	 * Returns an inline-embedable JS snippet to get the current value of the widget: e.g. $('#id').val() for simple inputs. 
	 * This snippet can be used to build interaction scripts between widgets.
	 * NOTE: the result does not end with a semicolon!
	 * 
	 * TODO add row and column to select a single value from the widgets data, which is generally represented by a DataSheet
	 * 
	 * @return string
	 */
	public function build_js_value_getter(){
		return '$("#' . $this->get_id() . '").' . $this->build_js_value_getter_method();
	}
	
	/**
	 * Returns the JS method to be called to get the current value of the widget: e.g. val() for simple inputs. Use this if your script
	 * needs to specifiy an element id explicitly - otherwise go for build_js_value_getter() which includes the id of the element.
	 * @see build_js_value_getter()
	 * @return string
	 */
	public function build_js_value_getter_method(){
		return 'val()';
	}
	
	/**
	 * Returns an inline JS snippet to set the current value of the widget: e.g. $('#id').val(value) for simple inputs. 
	 * This snippet can be used to build interaction scripts between widgets.
	 * 
	 * NOTE: the value can either be anything JS accepts as an argument: a scalar value, a variable, a funciton call 
	 * (e.g. generated by build_js_value_getter()) or an anonymous function, but it must be callable inline! In particular,
	 * there should not be a semicolon at the end!
	 * 
	 * @param string $value
	 * @return string
	 */
	public function build_js_value_setter($value){
		return '$("#' . $this->get_id() . '").' . $this->build_js_value_setter_method($value);
	}
	
	/**
	 * Returns the JS method to be called to set the current value of the widget: e.g. val(value) for simple inputs. Use this if your script
	 * needs to specifiy an element id explicitly - otherwise go for build_js_value_setter() which includes the id of the element.
	 * @see build_js_value_getter()
	 * @return string
	 */
	public function build_js_value_setter_method($value){
		return 'val(' . $value . ')';
	}
	
	/**
	 * Returns a JS snippet, that refreshes the contents of this element
	 * @return string
	 */
	public function build_js_refresh(){
		return '';
	}
	
	/**
	 * Returns the width of the element in CSS notation (e.g. 100px)
	 * @return string
	 */
	public function get_width(){
		$dimension = $this->get_widget()->get_width();
		if ($dimension->is_relative()){
			if ($dimension->get_value() != 'max'){
				$width = ($this->get_width_relative_unit() * $dimension->get_value()) . 'px';
			}
		} elseif ($dimension->is_template_specific() || $dimension->is_percentual()){
			$width = $dimension->get_value();
		} else {
			$width = ($this->get_width_relative_unit() * $this->get_width_default()) . 'px';
		}
		return $width;
	}
	
	/**
	 * Returns the height of the element in CSS notation (e.g. 100px)
	 * @return string
	 */
	public function get_height(){
		$dimension = $this->get_widget()->get_height();
		if ($dimension->is_relative()){
			$height = $this->get_height_relative_unit() * $dimension->get_value() . 'px';
		} elseif ($dimension->is_template_specific() || $dimension->is_percentual()){
			$height = $dimension->get_value();
		} else {
			$height = ($this->get_height_relative_unit() * $this->get_height_default()) . 'px';
		}
		return $height;
	}
	
	/**
	 * Returns the default relative height of this element
	 * @return string
	 */
	public function get_height_default() {
		if (is_null($this->height_default)){
			$this->height_default = $this->get_template()->get_config()->get_option('HEIGHT_DEFAULT');
		}
		return $this->height_default;
	}
	
	/**
	 * Sets the default relative height of this element
	 * @param string $value
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function set_height_default($value) {
		$this->height_default = $value;
		return $this;
	}
	
	/**
	 * Returns the default relative width of this element
	 * @return string
	 */
	public function get_width_default() {
		if (is_null($this->width_default)){
			$this->width_default = $this->get_template()->get_config()->get_option('WIDTH_DEFAULT');
		}
		return $this->width_default;
	}
	
	/**
	 * Sets the default relative width of this element
	 * @param string $value
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function set_width_default($value) {
		$this->width_default = $value;
		return $this;
	}
	
	/**
	 * Returns the width of one relative width unit in pixels
	 * @return \exface\Core\CommonLogic\multitype
	 */
	public function get_width_relative_unit(){
		if (is_null($this->width_relative_unit)){
			$this->width_relative_unit = $this->get_template()->get_config()->get_option('WIDTH_RELATIVE_UNIT');
		}
		return $this->width_relative_unit;
	}
	
	/**
	 * Returns the height of one relative height unit in pixels
	 * @return \exface\Core\CommonLogic\multitype
	 */
	public function get_height_relative_unit(){
		if (is_null($this->height_relative_unit)){
			$this->height_relative_unit = $this->get_template()->get_config()->get_option('HEIGHT_RELATIVE_UNIT');
		}
		return $this->height_relative_unit;
	}
	
	/**
	 * Returns an inline-embeddable JS snippet, that produces a JS-object ready to be encoded and sent to the server to
	 * perform the given action: E.g. {"oId": "UID of the meta object", "rows": [ {"col": "value, "col": "value, ...}, {...}, ... ] }.
	 * Each element can decide itself, which data it should return for which type of action. If no action is given, the entire data
	 * set used in the element should be returned.
	 *
	 * In contrast to build_js_value_getter(), which returns a value without context, the data getters return JS-representations of
	 * data sheets - thus, the data is alwas bound to a meta object.
	 *
	 * @param ActionInterface $action
	 * @return string
	 */
	public function build_js_data_getter(ActionInterface $action = null){		
		if ($this->get_widget() instanceof iShowSingleAttribute){
			$alias = $this->get_widget()->get_attribute_alias();
		} else {
			$alias = $this->get_widget()->get_meta_object()->get_alias_with_namespace();
		}
		return "{oId: '" . $this->get_widget()->get_meta_object_id() . "', rows: [{'" . $alias . "': " . $this->build_js_value_getter() . "}]}";
	}
	
	/**
	 * Adds a JavaScript snippet to the script, that will get executed every time the value of this element changes
	 * @param string $string
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function add_on_change_script($string){
		$this->on_change_script .= $string;
		return $this;
	}
	
	/**
	 * Returns the JavaScript snippet, that should get executed every time the value of this element changes
	 * @return string
	 */
	public function get_on_change_script(){
		return $this->on_change_script;
	}
	
	/**
	 * Overwrites the JavaScript snippet, that will get executed every time the value of this element changes
	 * @param string $string
	 */
	public function set_on_change_script($string){
		$this->on_change_script = $string;
		return $this;
	}
	
	/**
	 * Returns the JavaScript snippet, that should get executed every time the size of this element changes
	 * @return string
	 */
	public function get_on_resize_script() {
		return $this->on_resize_script;
	}
	
	/**
	 * Overwrites the JavaScript snippet, that will get executed every time the size of this element changes
	 * @param string $value
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function set_on_resize_script($value) {
		$this->on_resize_script = $value;
		return $this;
	}
	
	/**
	 * Adds a JavaScript snippet to the script, that will get executed every time the size of this element changes
	 * @param string $js
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function add_on_resize_script($js) {
		$this->on_resize_script .= $js;
		return $this;
	}
	
	/**
	 * Returns an JS-snippet to show a busy symbol (e.g. hourglass, spinner). This centralized method is used in various traits.
	 * @retrun string
	 */
	abstract public function build_js_busy_icon_show();
	
	/**
	 * Returns an JS-snippet to hide the busy symbol (e.g. hourglass, spinner). This centralized method is used in various traits.
	 * @retrun string
	 */
	abstract public function build_js_busy_icon_hide();
	
	/**
	 * Returns a JS snippet showing an error message. Body and title may be any JavaScript or quoted text (quotes will not be
	 * added automatically!!!).
	 *
	 * @param string $message_body_js
	 * @param string $title_js
	 * @return string
	 */
	public function build_js_show_message_error($message_body_js, $title_js = null){
		return "alert(" . $message_body_js . ");";
	}
	
	/**
	 * Returns a JS snippet showing a success notification. The body of the message may be any JavaScript or quoted text (quotes will not be
	 * added automatically!!!).
	 *
	 * @param string $message_body_js
	 * @param string $title
	 * @return string
	 */
	public function build_js_show_message_success($message_body_js, $title = null){
		return '';
	}
	
	/**
	 * Returns a JS snippet showing a server error. Body and title may be any JavaScript or quoted text (quotes will not be
	 * added automatically!!!).
	 *
	 * @param string $message_body_js
	 * @param string $title_js
	 * @return string
	 */
	public function build_js_show_error($message_body_js, $title_js = null){
		return "alert(" . $message_body_js . ");";
	}
	
	/**
	 * Returns a template specific CSS class for a given icon. In most templates this string will be used as a class for an <a> or <i> element.
	 * @param string $icon_name
	 * @return string
	 */
	public function build_css_icon_class($icon_name){
		try {
			$class = $this->get_template()->get_config()->get_option('ICON_CLASSES.' . strtoupper($icon_name));
			return $class;
		} catch (ConfigOptionNotFoundError $e) {
			return $this->get_template()->get_config()->get_option('ICON_CLASSES.DEFAULT_CLASS_PREFIX') . $icon_name;
		}
	}
	
	/**
	 * 
	 * @return \exface\Core\CommonLogic\Workbench
	 */
	public function get_workbench(){
		return $this->get_template()->get_workbench();
	}
	
	/**
	 * Returns the translation string for the given message id.
	 *
	 * This is a shortcut for calling $this->get_template()->get_app()->get_translator()->translate().
	 *
	 * @see Translation::translate()
	 *
	 * @param string $message_id
	 * @param array $placeholders
	 * @param float $number_for_plurification
	 * @return string
	 */
	public function translate($message_id, array $placeholders = array(), $number_for_plurification = null){
		$message_id = trim($message_id);
		return $this->get_template()->get_app()->get_translator()->translate($message_id, $placeholders, $number_for_plurification);
	}
	
	/**
	 * Returns an inline JS snippet which validates the widget. Returns true if the widget is
	 * valid, returns false if the widget is invalid.
	 *
	 * @return string
	 */
	public function build_js_validator(){
		return 'true';
	}
	
	/**
	 * Returns a JavaScript snippet which handles the situation where the widget is invalid e.g.
	 * by overwriting this function the widget could be highlighted or an error message could be
	 * shown.
	 *
	 * @return string
	 */
	public function build_js_validation_error(){
		return '';
	}
	
	/**
	 * Returns an inline JS snippet which enables the widget.
	 *
	 * @return string
	 */
	public function build_js_enabler(){
		return '$("#' . $this->get_id() . '").prop("disabled", false)';
	}
	
	/**
	 * Returns an inline JS snippet which disables the widget.
	 *
	 * @return string
	 */
	public function build_js_disabler(){
		return '$("#' . $this->get_id() . '").prop("disabled", true)';
	}
}
?>