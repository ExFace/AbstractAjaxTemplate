<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\Filter;

/**
 * 
 * 
 * @method Filter get_widget()
 * 
 * @author aka
 *
 */
trait JqueryFilterTrait {
	
	public function build_js_condition_getter(){
		$widget = $this->get_widget();
		return '{expression: "' . $widget->get_attribute_alias() . '", comparator: "' . $widget->get_comparator() . '", value: ' . $this->build_js_value_getter() . ', object_alias: "' . $widget->get_meta_object()->get_alias_with_namespace() . '"}';
	}
	
	public function generate_html(){
		return $this->get_input_element()->generate_html();
	}
	
	public function generate_js(){
		return $this->get_input_element()->generate_js();
	}
	
	public function build_js_value_getter(){
		return $this->get_input_element()->build_js_value_getter();
	}
	
	public function build_js_value_getter_method(){
		return $this->get_input_element()->build_js_value_getter_method();
	}
	
	public function build_js_value_setter($value){
		return $this->get_input_element()->build_js_value_setter($value);
	}
	
	public function build_js_value_setter_method($value){
		return $this->get_input_element()->build_js_value_setter_method($value);
	}
	
	public function build_js_init_options(){
		return $this->get_input_element()->build_js_init_options();
	}
	
	public function get_input_element(){
		return $this->get_template()->get_element($this->get_widget()->get_widget());
	}
	
	/**
	 * Magic method to forward all calls to methods, not explicitly defined in the filter to ist value widget.
	 * Thus, the filter is a simple proxy from the point of view of the template. However, it can be easily
	 * enhanced with additional methods, that will override the ones of the value widget.
	 * TODO this did not really work so far. Don't know why. As a work around, added some explicit proxy methods
	 * -> __call wird aufgerufen, wenn eine un!zugreifbare Methode in einem Objekt aufgerufen wird
	 * (werden die ueberschriebenen Proxymethoden entfernt, existieren sie ja aber auch noch euiInput)
	 * @param string $name
	 * @param array $arguments
	 */
	public function __call($name, $arguments){
		return call_user_method_array($name, $this->get_input_element(), $arguments);
	}

}
?>
