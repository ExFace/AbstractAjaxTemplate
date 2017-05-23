<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Factories\WidgetLinkFactory;

trait JqueryLiveReferenceTrait {
	
	protected function build_js_live_reference(){
		$output = '';
		if ($link = $this->get_widget()->get_value_widget_link()){
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
		if ($link = $this->get_widget()->get_value_widget_link()){
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
		}
		return $linked_element;
	}
	
	/**
	 * Returns a JavaScript-snippet, which is registered in the onChange-Script of the
	 * element linked by the disable condition. Based on the condition and the value
	 * of the linked widget, it enables and disables the current widget. 
	 * 
	 * @return string
	 */
	public function build_js_disable_condition(){
		$output = '';
		if (($condition = $this->get_widget()->get_disable_condition()) && $condition->widget_link){
			$link = WidgetLinkFactory::create_from_anything($this->get_workbench(), $condition->widget_link);
			$link->set_widget_id_space($this->get_widget()->get_id_space());
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($linked_element){
				switch ($condition->comparator) {
					case EXF_COMPARATOR_IS_NOT: // !=
					case EXF_COMPARATOR_EQUALS: // ==
					case EXF_COMPARATOR_EQUALS_NOT: // !==
					case EXF_COMPARATOR_LESS_THAN: // <
					case EXF_COMPARATOR_LESS_THAN_OR_EQUALS: //<=
					case EXF_COMPARATOR_GREATER_THAN: // >
					case EXF_COMPARATOR_GREATER_THAN_OR_EQUALS: // >=
						$enable_widget_script = $this->get_widget()->is_disabled() ? '' :
							$this->build_js_enabler() . ';
							// Sonst wird ein leeres required Widget nicht als invalide angezeigt
							$("#' . $this->get_id() . '").' . $this->get_element_type() . '("validate");';
						
						$output = <<<JS

						if ({$linked_element->build_js_value_getter($link->get_column_id())} {$condition->comparator} "{$condition->value}") {
							{$this->build_js_disabler()};
						} else {
							{$enable_widget_script}
						}
JS;
						break;
					case EXF_COMPARATOR_IN: // [
					case EXF_COMPARATOR_NOT_IN: // ![
					case EXF_COMPARATOR_IS: // =
					default:
						// TODO fuer diese Comparatoren muss noch der JavaScript generiert werden
				}
			}
		}
		return $output;
	}
	
	/**
	 * Returns a JavaScript-snippet, which initializes the disabled-state of elements
	 * with a disabled condition.
	 * 
	 * @return string
	 */
	public function build_js_disable_condition_initializer(){
		$output = '';
		if (($condition = $this->get_widget()->get_disable_condition()) && $condition->widget_link){
			$link = WidgetLinkFactory::create_from_anything($this->get_workbench(), $condition->widget_link);
			$link->set_widget_id_space($this->get_widget()->get_id_space());
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
			if ($linked_element){
				switch ($condition->comparator) {
					case EXF_COMPARATOR_IS_NOT: // !=
					case EXF_COMPARATOR_EQUALS: // ==
					case EXF_COMPARATOR_EQUALS_NOT: // !==
					case EXF_COMPARATOR_LESS_THAN: // <
					case EXF_COMPARATOR_LESS_THAN_OR_EQUALS: //<=
					case EXF_COMPARATOR_GREATER_THAN: // >
					case EXF_COMPARATOR_GREATER_THAN_OR_EQUALS: // >=
						$output .= <<<JS

						// Man muesste eigentlich schauen ob ein bestimmter Wert vorhanden ist: build_js_value_getter(link->get_column_id()).
						// Da nach einem Prefill dann aber normalerweise ein leerer Wert zurueckkommt, wird beim initialisieren
						// momentan einfach geschaut ob irgendein Wert vorhanden ist.
						if ({$linked_element->build_js_value_getter()} {$condition->comparator} "{$condition->value}") {
							{$this->build_js_disabler()};
						}
JS;
						break;
					case EXF_COMPARATOR_IN: // [
					case EXF_COMPARATOR_NOT_IN: // ![
					case EXF_COMPARATOR_IS: // =
					default:
						// TODO fuer diese Comparatoren muss noch der JavaScript generiert werden
				}
			}
		}
		return $output;
	}
	
	/**
	 * Registers an onChange-Skript on the element linked by the disable condition.
	 * 
	 * @return \exface\AbstractAjaxTemplate\Template\Elements\JqueryLiveReferenceTrait
	 */
	protected function register_disable_condition_at_linked_element(){
		if ($linked_element = $this->get_disable_condition_template_element()){
			$linked_element->add_on_change_script($this->build_js_disable_condition());
		}
		return $this;
	}
	
	/**
	 * Returns the widget which is linked by the disable condition.
	 * 
	 * @return 
	 */
	public function get_disable_condition_template_element(){
		$linked_element = null;
		if (($condition = $this->get_widget()->get_disable_condition()) && $condition->widget_link){
			$link = WidgetLinkFactory::create_from_anything($this->get_workbench(), $condition->widget_link);
			$link->set_widget_id_space($this->get_widget()->get_id_space());
			$linked_element = $this->get_template()->get_element_by_widget_id($link->get_widget_id(), $this->get_page_id());
		}
		return $linked_element;
	}
}
?>
