<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\Form;

/**
 * 
 * @method Form get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait JqueryFormTrait {
	
	function build_html_buttons(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_html($btn);
		}
		
		return $output;
	}
	
	function build_js_buttons(){
		$output = '';
		foreach ($this->get_widget()->get_buttons() as $btn){
			$output .= $this->get_template()->generate_js($btn);
		}
		
		return $output;
	}
	
}
?>