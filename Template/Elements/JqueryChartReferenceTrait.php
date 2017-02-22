<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

trait JqueryChartReferenceTrait {
	
	protected function build_js_live_reference(){
		$output = '';
		if ($link = $this->get_widget()->get_data_widget_link()){
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			$output .= $this->build_js_function_prefix() . 'plot(' . $linked_element->build_js_data_getter() . ".rows);";
		}
		return $output;
	}
	
	/**
	 * Makes sure, the Chart is always updated, once the linked data widget loads new data - of course, only if there is a data link defined!
	 * @return euiChart
	 */
	protected function register_live_reference_at_linked_element(){
		if ($link = $this->get_widget()->get_data_widget_link()){
			/* @var $linked_element \exface\Templates\jEasyUI\Widgets\euiData */
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($linked_element){
				$linked_element->add_on_load_success($this->build_js_live_reference());
			}
		}
		return $this;
	}
	
}
?>
