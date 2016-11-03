<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\AbstractWidget;
use exface\Core\Interfaces\TemplateInterface;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\AbstractAjaxTemplate\Template\AbstractAjaxTemplate;
use exface\Core\Exceptions\TemplateError;

abstract class AbstractJqueryElement {
	
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
	
	public function __construct(WidgetInterface $widget, TemplateInterface $template){
		$this->exf_widget = $widget;
		$this->template = $template;
		$this->init();
	}
	
	/**
	 * This method is run every time the element is created. Override it to set inherited options.
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
	 *
	 * @return AbstractWidget
	 */
	public function get_widget() {
		return $this->exf_widget;
	}
	
	public function set_widget(AbstractWidget $value) {
		$this->exf_widget = $value;
	}
	
	/**
	 * Returns the template engine
	 * @return AbstractAjaxTemplate
	 */
	public function get_template(){
		return $this->template;
	}
	
	/**
	 * 
	 * @return \exface\Core\CommonLogic\Model\Object
	 */
	public function get_meta_object(){
		return $this->get_widget()->get_meta_object();
	}
	
	/**
	 * 
	 * @return string
	 */
	public function get_page_id() {
		return $this->get_widget()->get_page()->get_id();
	}
	
	public function get_hint_max_chars_in_line() {
		if (is_null($this->hint_max_chars_in_line)){
			$this->hint_max_chars_in_line = $this->get_template()->get_config()->get_option('HINT_MAX_CHARS_IN_LINE');
		}
		return $this->hint_max_chars_in_line;
	}
	
	public function set_hint_max_chars_in_line($value) {
		$this->hint_max_chars_in_line = $value;
	}
	
	public function get_hint($hint_text = NULL, $remove_linebreaks = false){
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
	
	public function get_ajax_url() {
		if (is_null($this->ajax_url)){
			$this->ajax_url = $this->get_template()->get_config()->get_option('DEFAULT_AJAX_URL');
		}
		$request_id = $this->get_template()->get_workbench()->get_request_id();
		return $this->ajax_url . ($request_id ? '&exfrid=' . $request_id : '');
	}
	
	public function set_ajax_url($value) {
		$this->ajax_url = $value;
	}
	
	public function get_function_prefix(){
		if (is_null($this->function_prefix)){
			$this->function_prefix = str_replace($this->get_template()->get_config()->get_option('FORBIDDEN_CHARS_IN_FUNCTION_PREFIX'), '_', $this->get_id()) . '_';
		}
		return $this->function_prefix;
	}
	
	/**
	 * Returns the type attribute of the resulting HTML-element. In pure HTML this is only usefull for elements like
	 * input fields (the type would be "text", "hidden", etc.), but many UI-frameworks use this kind of attribute
	 * to identify types of widgets. Returns NULL by default.
	 * 
	 * @return string
	 */
	public function get_element_type() {
		return $this->element_type;
	}
	
	/**
	 * 
	 * @param string $value
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement
	 */
	public function set_element_type($value) {
		$this->element_type = $value;
		return $this;
	}
	
	/**
	 * Returns the id of the HTML-element representing the widget. Passing a widget id makes the method return the id of the element
	 * that belongs to that widget.
	 * 
	 * @param string $widget_id
	 * @return string
	 */
	function get_id($widget_id = null){
		if (!is_null($widget_id)){
			if (!$element = $this->get_template()->get_element_by_widget_id($widget_id, $this->get_page_id())){
				throw new TemplateError('No element for widget id "' . $widget_id . '" found on page "' . $this->get_page_id() . '"!');	
				return '';
			}
			return $element->get_id();
		}
		if (is_null($this->id)){
			$this->id = $this->clean_id($this->get_widget()->get_id()) . ($this->get_template()->get_workbench()->get_request_id() ? '-' . $this->get_template()->get_workbench()->get_request_id() : '');
		}
		return $this->id;
	}
	
	/**
	 * Replaces all characters, which are not supported in the ids of DOM-elements (i.e. "/" etc.)
	 * @param string $id
	 * @return string
	 */
	function clean_id($id){
		return str_replace($this->get_template()->get_config()->get_option('FORBIDDEN_CHARS_IN_ELEMENT_ID'), '_', $id);
	}
	
	/**
	 * Returns a JS snippet to get the current value of the widget: e.g. $('#id').val() for simple inputs. This snippet can be used
	 * to build interaction scripts between widgets.
	 * TODO add row and column to select a single value from the widgets data, which is generally
	 * represented by a DataSheet
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
	function build_js_value_getter_method(){
		return 'val()';
	}
	
	/**
	 * Returns a JS snippet to set the current value of the widget: e.g. $('#id').val(value) for simple inputs. This snippet can be used
	 * to build interaction scripts between widgets.
	 * NOTE: the value can either be anything JS accepts as an argument: a scalar value, a variable, a funciton call (e.g. generated by
	 * build_js_value_getter()) or an anonymous function.
	 * @param string $value
	 * @return string
	 */
	function build_js_value_setter($value){
		return '$("#' . $this->get_id() . '").' . $this->build_js_value_setter_method($value);
	}
	
	/**
	 * Returns the JS method to be called to set the current value of the widget: e.g. val(value) for simple inputs. Use this if your script
	 * needs to specifiy an element id explicitly - otherwise go for build_js_value_setter() which includes the id of the element.
	 * @see build_js_value_getter()
	 * @return string
	 */
	function build_js_value_setter_method($value){
		return 'val(' . $value . ')';
	}
	
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
	
	public function get_height_default() {
		if (is_null($this->height_default)){
			$this->height_default = $this->get_template()->get_config()->get_option('HEIGHT_DEFAULT');
		}
		return $this->height_default;
	}
	
	public function set_height_default($value) {
		$this->height_default = $value;
		return $this;
	}
	
	public function get_width_default() {
		if (is_null($this->width_default)){
			$this->width_default = $this->get_template()->get_config()->get_option('WIDTH_DEFAULT');
		}
		return $this->width_default;
	}
	
	public function set_width_default($value) {
		$this->width_default = $value;
		return $this;
	}
	
	public function get_width_relative_unit(){
		if (is_null($this->width_relative_unit)){
			$this->width_relative_unit = $this->get_template()->get_config()->get_option('WIDTH_RELATIVE_UNIT');
		}
		return $this->width_relative_unit;
	}
	
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
	 * In contrast to build_js_value_getter(), which returns a value without context, the data getters retunr JS-representations of
	 * data sheets - thus, the data is alwas bound to a meta object.
	 *
	 * @param ActionInterface $action
	 * @return string
	 */
	public function build_js_data_getter(ActionInterface $action = null, $custom_body_js = null){
		if (is_null($custom_body_js)){
			if (method_exists($this->get_widget(), 'get_attribute_alias')){
				$alias = $this->get_widget()->get_attribute_alias();
			} else {
				$alias = $this->get_widget()->get_meta_object()->get_alias_with_namespace();
			}
			$custom_body_js = "data.rows = [{'" . $alias . "': " . $this->build_js_value_getter() . "}]";
		}
	
		$js = <<<JS
		(function(){
			var data = {};
			data.oId = '{$this->get_widget()->get_meta_object_id()}';
			{$custom_body_js}
			return data;
		})()
JS;
				return $js;
	}
	
	public function add_on_change_script($string){
		$this->on_change_script .= $string;
		return $this;
	}
	
	public function get_on_change_script(){
		return $this->on_change_script;
	}
	
	public function set_on_change_script($string){
		$this->on_change_script = $string;
		return $this;
	}
	
	public function get_on_resize_script() {
		return $this->on_resize_script;
	}
	
	public function set_on_resize_script($value) {
		$this->on_resize_script = $value;
		return $this;
	}
	
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
	
}
?>