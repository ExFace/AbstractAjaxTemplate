<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Container;

/**
 * 
 * @method Container get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
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
		// Collect JS data objects from all inputs in the container
		foreach ($widget->get_input_widgets() as $child){
			if (!$child->implements_interface('iSupportStagedWriting')){
				$data_getters[] = $this->get_template()->get_element($child)->build_js_data_getter($action);
			} else {
				// TODO get data from non-input widgets, that support deferred CRUD operations staging their data in the GUI	
			}
		}
		if (count($data_getters) > 0){
			// Merge all the JS data objects, but remember to overwrite the head oId in the resulting object with the object id 
			// of the container itself at the end! Otherwise the object id of the last widget in the container would win!
			return "$.extend(true, {},\n" . implode(",\n", $data_getters) . ",\n{oId: '" . $widget->get_meta_object_id() . "'}\n)";
		} else {
			return '{}';
		}
	}
	
}
?>