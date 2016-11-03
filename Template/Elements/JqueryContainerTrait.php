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
		$output = 'data.rows = []' . "\n";
		$found_inputs = false;
		foreach ($this->get_widget()->get_children_recursive() as $child){
			if ($child->implements_interface('iTakeInput')){
				if (!$found_inputs){
					$output .= 'data.rows[0] = {};';
					$found_inputs = true;
				}
				$output .= 'data.rows[0]["' . $child->get_attribute_alias() . '"] = ' . $this->get_template()->get_element($child)->build_js_value_getter() . ";\n";
			}
		}
		return parent::build_js_data_getter($action, $output . $custom_body_js);
	}
	
}
?>