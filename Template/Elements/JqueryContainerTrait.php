<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;

trait JqueryContainerTrait {
	
	public function generate_html(){
		return $this->build_html_for_children();
	}
	
	public function generate_js(){
		return $this->build_js_for_children();
	}
	
	public function build_html_for_children(){
		foreach ($this->get_widget()->get_children() as $subw){
			$output .= $this->get_template()->generate_html($subw) . "\n";
		};
		return $output;
	}
	
	public function build_js_for_children(){
		foreach ($this->get_widget()->get_children() as $subw){
			$output .= $this->get_template()->generate_js($subw) . "\n";
		};
		return $output;
	}
	
	public function build_html_for_widgets(){
		foreach ($this->get_widget()->get_widgets() as $subw){
			$output .= $this->get_template()->generate_html($subw) . "\n";
		};
		return $output;
	}
	
	public function build_js_for_widgets(){
		foreach ($this->get_widget()->get_widgets() as $subw){
			$output .= $this->get_template()->generate_js($subw) . "\n";
		};
		return $output;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::build_js_data_getter()
	 */
	public function build_js_data_getter(ActionInterface $action = null){
		/* @var $widget \exface\Core\Widgets\Container */
		$widget = $this->get_widget();
		$data_getters = array();
		foreach ($widget->get_input_widgets() as $child){
			// FIXME $child->get_meta_object()->is_exactly($action->get_meta_object()) makes it impossible to use widgets with custom objects in forms!
			if (!$child->implements_interface('iSupportStagedWriting')){
				// Collect data from all widgets that take regular input (all sorts of input fields, etc.). Make sure, only data directly related
				// to the object the action is performed upon is collected
				//$output_rows .= $js_data_variable . '.rows[0]["' . $child->get_attribute_alias() . '"] = ' . $this->get_template()->get_element($child)->build_js_value_getter() . ";\n";
				$data_getters[] = $this->get_template()->get_element($child)->build_js_data_getter($action);
			} else {
				// TODO get data from non-input widgets, that support deferred CRUD operations staging their data in the GUI	
			}
		}
		return "$.extend(true, {},\n" . implode(",\n", $data_getters) . "\n)";
	}
	
}
?>