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
	public function build_js_data_getter(ActionInterface $action = null, $custom_body_js = null){
		/* @var $widget \exface\Core\Widgets\Container */
		$widget = $this->get_widget();
		$output_rows = '';
		$found_inputs = false;
		foreach ($widget->get_children_recursive() as $child){
			if ($child->implements_interface('iTakeInput') && $child->get_meta_object()->is($action->get_meta_object())){				
				// Collect data from all widgets that take regular input (all sorts of input fields, etc.). Make sure, only data directly related
				// to the object the action is performed upon is collected
				$output_rows .= 'data.rows[0]["' . $child->get_attribute_alias() . '"] = ' . $this->get_template()->get_element($child)->build_js_value_getter() . ";\n";
			} elseif ($child->implements_interface('iSupportStagedWriting')){
				// TODO get data from non-input widgets, that support deferred CRUD operations staging their data in the GUI	
			}
		}
		
		// In any case, create a rows property for the data JS-object
		$output = 'data.rows = [];' . "\n";
		// If any script for the data.rows was generated, initialize the first row as a generic object and add that script afterwards
		if ($output_rows){
			$output .= 'data.rows[0] = {};' . "\n";
			$output .= $output_rows;
		}
		
		return parent::build_js_data_getter($action, $output . $custom_body_js);
	}
	
}
?>