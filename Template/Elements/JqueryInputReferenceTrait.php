<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

trait JqueryInputReferenceTrait {
	
	protected function build_js_live_reference(){
		$output = '';
		if ($this->get_widget()->get_value_expression() && $this->get_widget()->get_value_expression()->is_reference()){
			$link = $this->get_widget()->get_value_expression()->get_widget_link();
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($linked_element){
				$output = '
						' . $this->build_js_value_setter($linked_element->build_js_value_getter($link->get_column_id())) . ';';
			}
		}
		return $output;
	}
	
	/**
	 * Makes sure, this element is always updated, once the value of a live reference changes - of course, only if there is a live reference!
	 * @return euiInput
	 */
	protected function register_live_reference_at_linked_element(){
		if ($linked_element = $this->get_linked_template_element()){
			$linked_element->add_on_change_script($this->build_js_live_reference());
		}
		return $this;
	}
	
	public function get_linked_template_element(){
		$linked_element = null;
		if ($this->get_widget()->get_value_expression() && $this->get_widget()->get_value_expression()->is_reference()){
			$link = $this->get_widget()->get_value_expression()->get_widget_link();
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
		}
		return $linked_element;
	}
}
?>
