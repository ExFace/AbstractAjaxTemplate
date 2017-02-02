<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Actions\GoBack;
use exface\Core\Widgets\Button;

trait JqueryButtonTrait {
	
	protected function build_js_input_refresh(Button $widget, $input_element){
		$js = ($widget->get_refresh_input() && $input_element->build_js_refresh() ? $input_element->build_js_refresh() . ";" : "");
		if ($link = $widget->get_refresh_widget_link()){
			if($link->get_page_id() == $widget->get_page_id() && $linked_element = $this->get_template()->get_element($link->get_widget())){
				$js .= "\n" . $linked_element->build_js_refresh();
			}
		}
		return $js;
	}
	
	public function build_js_click_function_name(){
		return $this->build_js_function_prefix() . 'click';
	}
	
	/**
	 * Returns a javascript snippet, that replaces all placholders in a give string by values from a given javascript object.
	 * Placeholders must be in the general ExFace syntax [#placholder#], while the value object must have a property for every
	 * placeholder with the same name (without "[#" and "#]"!).
	 * @param string $js_var - e.g. result (the variable must be already instantiated!)
	 * @param string $js_values_array - e.g. values = {placeholder = "someId"}
	 * @param string $string_with_placeholders - e.g. http://localhost/pages/[#placeholder#]
	 * @param string $js_sanitizer_function - a Javascript function to be applied to each value (e.g. encodeURIComponent) - without braces!!!
	 * @return string - e.g. result = result.replace('[#placeholder#]', values['placeholder']);
	 */
	protected function build_js_placeholder_replacer($js_var, $js_values_object, $string_with_placeholders, $js_sanitizer_function = null){
		$output = '';
		$placeholders = $this->get_template()->get_workbench()->utils()->find_placeholders_in_string($string_with_placeholders);
		foreach ($placeholders as $ph){
			$value = $js_values_object . "['" . $ph . "']";
			if ($js_sanitizer_function){
				$value = $js_sanitizer_function . '(' . $value . ')';
			}
			$output .= $js_var . " = " . $js_var . ".replace('[#" . $ph . "#]', " . $value . ");";
		}
		return $output;
	}
	
	protected function build_js_request_data_collector(ActionInterface $action, AbstractJqueryElement $input_element){
		if (!is_null($action->get_input_rows_min()) || !is_null($action->get_input_rows_max())){
			if ($action->get_input_rows_min() === $action->get_input_rows_max()){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select exactly " . $action->get_input_rows_min() . " row(s)!'); return false;}";
			} elseif (is_null($action->get_input_rows_max())){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . ") {alert('Please select at least " . $action->get_input_rows_min() . " row(s)!'); return false;}";
			} elseif (is_null($action->get_input_rows_min())){
				$js_check_input_rows = "if (requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select at most " . $action->get_input_rows_max() . " row(s)!'); return false;}";
			} else {
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {alert('Please select from " . $action->get_input_rows_min() . " to " . $action->get_input_rows_max() . " rows first!'); return false;}";
			}
		} else {
			$js_check_input_rows = '';
		}
		
		/*if (!is_null($action->get_input_rows_min()) || !is_null($action->get_input_rows_max())){
			if ($action->get_input_rows_min() === $action->get_input_rows_max()){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {" . $this->build_js_show_error_message('"Please select exactly ' . $action->get_input_rows_min() . ' row(s)!"') . " return false;}";
			} elseif (is_null($action->get_input_rows_max())){
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . ") {" . $this->build_js_show_error_message('"Please select at least ' . $action->get_input_rows_min() . ' row(s)!"') . " return false;}";
			} elseif (is_null($action->get_input_rows_min())){
				$js_check_input_rows = "if (requestData.rows.length > " . $action->get_input_rows_max() . ") {" . $this->build_js_show_error_message('"Please select at most ' . $action->get_input_rows_max() . ' row(s)!"') . " return false;}";
			} else {
				$js_check_input_rows = "if (requestData.rows.length < " . $action->get_input_rows_min() . " || requestData.rows.length > " . $action->get_input_rows_max() . ") {" . $this->build_js_show_error_message('"Please select from ' . $action->get_input_rows_min() . ' to ' . $action->get_input_rows_max() . ' rows first!"') . " return false;}";
			}
		} else {
			$js_check_input_rows = '';
		}*/
		
		$js_requestData = "
					var requestData = " . $input_element->build_js_data_getter($action) . ";
					" . $js_check_input_rows;
		return $js_requestData;
	}
	
	/**
	 * @return ActionInterface
	 */
	protected function get_action(){
		return $this->get_widget()->get_action();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement::get_widget()
	 * @return Button
	 */
	public function get_widget(){
		return parent::get_widget();
	}
	
	public function build_js_click_function(){
		$output = '';
		$widget = $this->get_widget();
		$input_element = $this->get_template()->get_element($widget->get_input_widget(), $this->get_page_id());
	
		$action = $widget->get_action();
	
		// if the button does not have a action attached, just see if the attributes of the button
		// will cause some click-behaviour and return the JS for that
		if (!$action) {
			$output .= $this->build_js_close_dialog($widget, $input_element)
			. $this->build_js_input_refresh($widget, $input_element);
			return $output;
		}
	
		if ($action->implements_interface('iRunTemplateScript')){
			$output = $this->build_js_click_run_template_script($action, $input_element);
		} elseif ($action->implements_interface('iShowDialog')) {
			$output = $this->build_js_click_show_dialog($action, $input_element);
		} elseif ($action->implements_interface('iShowUrl')) {
			$output = $this->build_js_click_show_url($action, $input_element);
		} elseif ($action->implements_interface('iShowWidget')) {
			$output = $this->build_js_click_show_widget($action, $input_element);
		} elseif ($action instanceof GoBack){
			$output = $this->build_js_click_go_back($action, $input_element);
		} else {
			$output = $this->build_js_click_call_server_action($action, $input_element);
		}
	
		return $output;
	}
	
	protected function build_js_click_call_server_action(ActionInterface $action, AbstractJqueryElement $input_element){
		$widget = $this->get_widget();
		
		$output = $this->build_js_request_data_collector($action, $input_element);
		$output .= "
						" . $this->build_js_busy_icon_show() . "
						$.ajax({
							type: 'POST',
							url: '" . $this->get_ajax_url() ."',
							data: {	
								action: '".$widget->get_action_alias()."',
								resource: '" . $widget->get_page_id() . "',
								element: '" . $widget->get_id() . "',
								object: '" . $widget->get_meta_object_id() . "',
								data: requestData
							},
							success: function(data, textStatus, jqXHR) {
								var response = {};
								try {
									response = $.parseJSON(data);
								} catch (e) {
									response.error = data;
								}
			                   	if (response.success){
									" . $this->build_js_close_dialog($widget, $input_element) . "
									" . $this->build_js_input_refresh($widget, $input_element) . "
			                       	" . $this->build_js_busy_icon_hide() . "
									if (response.success || response.undoURL){
			                       		" . $this->build_js_show_success_message("response.success + (response.undoable ? ' <a href=\"" . $this->build_js_undo_url($action, $input_element) . "\" style=\"display:block; float:right;\">UNDO</a>' : '')") . "
									}
			                    } else {
									" . $this->build_js_busy_icon_hide() . "
									" . $this->build_js_show_error_message('response.error', '"Server error"') . "
			                    }
							},
							error: function(jqXHR, textStatus, errorThrown){ 
								" . $this->build_js_show_error_message('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText') . " 
								" . $this->build_js_busy_icon_hide() . "
							}
						});
					";
		
		return $output;
		
		/*
		return $this->build_js_request_data_collector($action, $input_element) . "
						" . $input_element->build_js_busy_icon_show() . "
						$.ajax({
							url: '" . $this->get_ajax_url() ."',
							type: 'POST',
							data: {
								action: '".$widget->get_action_alias()."',
								resource: '".$widget->get_page_id()."',
								element: '".$widget->get_id()."',
								object: '" . $widget->get_meta_object_id() . "',
								data: requestData
							},
							success: function(data, textStatus, jqXHR) {
								" . $this->build_js_close_dialog($widget, $input_element) . "
					            " . $this->build_js_input_refresh($widget, $input_element) . "
								" . $input_element->build_js_busy_icon_hide() . "
							},
					        error: function(jqXHR, textStatus, errorThrown)
					        {
					            " . $input_element->build_js_busy_icon_hide() . "
			            		" . $this->build_js_show_error_message('jqXHR.responseText', '"Server error"') . "
					        }
						});";*/
	}
	
	protected function build_js_click_show_widget(ActionInterface $action, AbstractJqueryElement $input_element){
		$widget = $this->get_widget();
		$output = '';
		if ($action->get_page_id() != $this->get_page_id()){
			$output = $this->build_js_request_data_collector($action, $input_element) . $input_element->build_js_busy_icon_show() . "
			 	window.location.href = '" . $this->get_template()->create_link_internal($action->get_page_id()) . "?prefill={\"meta_object_id\":\"" . $widget->get_meta_object_id() . "\",\"rows\":[{\"" . $widget->get_meta_object()->get_uid_alias() . "\":\"' + requestData.rows[0]." . $widget->get_meta_object()->get_uid_alias() . " + '\"}]}';";
		}
		return $output;
	}

	protected function build_js_click_go_back(ActionInterface $action, AbstractJqueryElement $input_element){
		return $input_element->build_js_busy_icon_show() . 'parent.history.back(); return false;';
	}
	
	protected function build_js_click_show_url(ActionInterface $action, AbstractJqueryElement $input_element){
		/* @var $action \exface\Core\Interfaces\Actions\iShowUrl */
		$output = $this->build_js_request_data_collector($action, $input_element) . "
					var " . $action->get_alias() . "Url='" . $action->get_url() . "';
					" . $this->build_js_placeholder_replacer($action->get_alias() . "Url", "requestData.rows[0]", $action->get_url(), ($action->get_urlencode_placeholders() ? 'encodeURIComponent' : null));
		if ($action->get_open_in_new_window()){
			$output .= $input_element->build_js_busy_icon_show() . "window.open(" . $action->get_alias() . "Url);" . $input_element->build_js_busy_icon_hide();
		} else {
			$output .= $input_element->build_js_busy_icon_show() . "window.location.href = " . $action->get_alias() . "Url;";
		}
		return $output;
	}
	
	protected function build_js_click_run_template_script(ActionInterface $action, AbstractJqueryElement $input_element){
		return $action->print_script($input_element->get_id());
	}
	
	protected function build_js_undo_url(ActionInterface $action, AbstractJqueryElement $input_element){
		$widget = $this->get_widget();
		if ($action->is_undoable()){
			$undo_url = $this->get_ajax_url() . "&action=exface.Core.UndoAction&resource=".$widget->get_page_id()."&element=".$widget->get_id();
		}
		return $undo_url;
	}
	
}
?>