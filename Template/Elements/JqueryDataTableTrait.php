<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\DataTable;

/**
 * This trait contains common methods for templates elements inheriting from the AbstractJqueryElement and representing
 * the DataTable widget.
 * 
 * @method DataTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait JqueryDataTableTrait {
	
	/**
	 * Builds an anonymous JS function returning the JSON representation of the condition group in the filters of the DataTable.
	 *
	 * This function is handy when building JS data or prefill objects.
	 *
	 * @return string
	 */
	protected function build_js_data_filters(){
		$widget = $this->get_widget();
		$detail_filters_js = '
				var filters = {operator: "AND", conditions: []}
			';
		foreach ($widget->get_filters() as $filter){
			$filter_element = $this->get_template()->get_element($filter);
			$detail_filters_js .= '
				if (' . $filter_element->build_js_value_getter() . '){
					filters.conditions.push(' . $filter_element->build_js_condition_getter() . ');
				}';
		}
		return 'function(){' . $detail_filters_js . ' return filters;}()';
	}
}
?>
