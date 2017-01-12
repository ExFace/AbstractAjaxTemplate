<?php namespace exface\AbstractAjaxTemplate\Template;

use exface\Core\CommonLogic\AbstractTemplate;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Widgets\Data;
use exface\Core\Widgets\AbstractWidget;
use exface\Core\Interfaces\Widgets\iTriggerAction;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\CommonLogic\WidgetLink;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\Factories\ActionFactory;
use exface\Core\Interfaces\WidgetInterface;
use exface\AbstractAjaxTemplate\Template\Elements\AbstractJqueryElement;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;
use exface\Core\Interfaces\Exceptions\WarningExceptionInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use exface\Core\Exceptions\Templates\TemplateRequestParsingError;
use exface\Core\Interfaces\UiPageInterface;
use exface\Core\Factories\UiPageFactory;

abstract class AbstractAjaxTemplate extends AbstractTemplate {
	private $elements = array();
	private $class_prefix = '';
	private $class_namespace = '';
	protected $request_id = null;
	protected $request_paging_offset = 0;
	protected $request_paging_rows = NULL;
	protected $request_filters_array = array();
	protected $request_quick_search_value = NULL;
	protected $request_sorting_sort_by = NULL;
	protected $request_sorting_direction = NULL;
	protected $request_widget_id = NULL;
	protected $request_page_id = NULL;
	protected $request_action_alias = NULL;
	protected $request_prefill_data = NULL;
	protected $request_system_vars = array();
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractTemplate::draw()
	 */
	function draw(\exface\Core\Widgets\AbstractWidget $widget){
		$output = '';
		try {
			$output .= $this->generate_html($widget);
			$js = $this->generate_js($widget);
		} catch (ErrorExceptionInterface $e){
			$output .= $this->generate_html($e->create_widget($widget->get_page()));
			$js .= $this->generate_js($e->create_widget($widget->get_page()));
		}
		if ($js){
			$output .= "\n" . '<script type="text/javascript">' . $js . '</script>';
		}
		
		return $output;
	}
	
	/**
	 * Generates the JavaScript for a given Widget
	 * @param \exface\Core\Widgets\AbstractWidget $widget
	 */
	function generate_js(\exface\Core\Widgets\AbstractWidget $widget){
		$instance = $this->get_element($widget);
		return $instance->generate_js();
	}
	
	/**
	 * Generates the HTML for a given Widget
	 * @param \exface\Core\Widgets\AbstractWidget $widget
	 */
	function generate_html(\exface\Core\Widgets\AbstractWidget $widget){
		$instance = $this->get_element($widget);
		return $instance->generate_html();
	}
	
	/**
	 * Generates the declaration of the JavaScript sources
	 * @return string
	 */
	public function draw_headers(\exface\Core\Widgets\AbstractWidget $widget){
		try {
			$instance = $this->get_element($widget);
			$result = implode("\n", array_unique($instance->generate_headers()));
		} catch (ErrorExceptionInterface $e){
			// TODO Is there a way to display errors in the header nicely?
			/*$ui = $this->get_workbench()->ui();
			$page = UiPageFactory::create($ui, 0);
			return $this->get_workbench()->get_debugger()->print_exception($e, false);*/
			throw $e;
		}
		return $result;
	}
	
	/**
	 * Creates a template element for a given ExFace widget.
	 * Elements are cached within the template engine, so multiple calls to this method do
	 * not cause the element to get recreated from scratch. This improves performance.
	 * 
	 * @param WidgetInterface $widget
	 * @return AbstractJqueryElement
	 */
	function get_element(\exface\Core\Widgets\AbstractWidget $widget){
		if (!array_key_exists($widget->get_page_id(), $this->elements) || !array_key_exists($widget->get_id(), $this->elements[$widget->get_page_id()])){
			$elem_class = $this->get_class($widget);
			$instance = new $elem_class($widget, $this);
			$this->elements[$widget->get_page_id()][$widget->get_id()] = $instance;
		}
		
		return $this->elements[$widget->get_page_id()][$widget->get_id()];
	}
	
	protected function get_class(WidgetInterface $widget){
		$elem_class_prefix = $this->get_class_namespace() .  '\\Elements\\' . $this->get_class_prefix();
		$elem_class = $elem_class_prefix . $widget->get_widget_type();
		if (!class_exists($elem_class)){
			$widget_class = get_parent_class($widget);
			$elem_class = $elem_class_prefix . AbstractWidget::get_widget_type_from_class($widget_class);
			while (!class_exists($elem_class)){
				if ($widget_class = get_parent_class($widget_class)){
					$elem_class = $elem_class_prefix . AbstractWidget::get_widget_type_from_class($widget_class);
				} else {
					break;
				}
			}
			
			if (class_exists($elem_class)){
				$reflection = new \ReflectionClass($elem_class);
				if ($reflection->isAbstract()){
					$elem_class = $elem_class_prefix . 'BasicElement';
				}
			} else {
				$elem_class = $elem_class_prefix . 'BasicElement';
			}
		}
		// if the required widget is not found, create an abstract widget instead
		return $elem_class;
	}
	
	/**
	 * Creates a template element for a widget of the give resource, specified by the
	 * widget's ID. It's just a shortcut in case you do not have the widget object at
	 * hand, but know it's ID and the resource, where it resides.
	 * 
	 * @param strig $widget_id
	 * @param string $page_id
	 * @return \exface\Templates\jeasyui\Widgets\jeasyuiAbstractWidget
	 */
	public function get_element_by_widget_id($widget_id, $page_id){
		if ($elem = $this->elements[$page_id][$widget_id]){
			return $elem;
		} else {
			if ($widget_id = $this->get_workbench()->ui()->get_widget($widget_id, $page_id)){
				return $this->get_element($widget_id);
			} else {
				return false;
			}
			
		}
	}
	
	public function get_element_from_widget_link(WidgetLink $link){
		return $this->get_element_by_widget_id($link->get_widget_id(), $link->get_page_id());
	}
	
	public function create_link_internal($page_id, $url_params=''){
		return $this->get_workbench()->cms()->create_link_internal($page_id, $url_params);
	}

	public function get_data_sheet_from_request($object_id = NULL, $widget = NULL) {
		if (!$this->request_data_sheet){
			// Look for filter data
			$filters = $this->get_request_filters();
			// Add filters for quick search
			if ($widget && $quick_search = $this->get_request_quick_search_value()){
				$quick_search_filter = $widget->get_meta_object()->get_label_alias();
				if ($widget->is('Data') && count($widget->get_attributes_for_quick_search()) > 0){
					foreach ($widget->get_attributes_for_quick_search() as $attr){
						$quick_search_filter .= ($quick_search_filter ? EXF_LIST_SEPARATOR : '') . $attr;
					}
				}
				if ($quick_search_filter){
					$filters[$quick_search_filter][] = $quick_search;
				} else {
					throw new TemplateRequestParsingError('Cannot perform quick search on object "' . $widget->get_meta_object()->get_alias_with_namespace() . '": either mark one of the attributes as a label in the model or set inlude_in_quick_search = true for one of the filters in the widget definition!', '6T6HSL4');
				}
			}
		
			// TODO this is a dirty hack. The special treatment for trees needs to move completely to the respective class
			if ($widget && $widget->get_widget_type() == 'DataTree' && !$filters['PARENT']){
				$filters['PARENT'][] = $widget->get_tree_root_uid();
			}
			
			/* @var $data_sheet \exface\Core\CommonLogic\DataSheets\DataSheet */
			if ($widget){
				$data_sheet = $widget->prepare_data_sheet_to_read();
			} elseif ($object_id) {
				$data_sheet = $this->get_workbench()->data()->create_data_sheet($this->get_workbench()->model()->get_object($object_id));
			} else {
				return null;
			}
			
			// Set filters
			foreach ($filters as $fltr_attr => $fltr){
				foreach ($fltr as $val){
					$data_sheet->add_filter_from_string($fltr_attr, $val);
				}
			}
			
			// Set sorting options
			$sort_by = $this->get_request_sorting_sort_by();
			$order = $this->get_request_sorting_direction();
			if ($sort_by && $order){
				$sort_by = explode(EXF_LIST_SEPARATOR, $sort_by);
				$order = explode(EXF_LIST_SEPARATOR, $order);
				foreach ($sort_by as $nr => $sort){
					$data_sheet->get_sorters()->add_from_string($sort, $order[$nr]);
				}
			}
			
			// Set pagination options
			$data_sheet->set_row_offset($this->get_request_paging_offset());
			$data_sheet->set_rows_on_page($this->get_request_paging_rows());
		
			// Look for actual data rows in the request
			if ($object_id){
				if ($this->get_workbench()->get_request_params()['data'] && !is_array($this->get_workbench()->get_request_params()['data'])){
					if ($decoded = @json_decode($this->get_workbench()->get_request_params()['data'], true));
					$this->get_workbench()->set_request_param('data', $decoded);
				}
				if (is_array($this->get_workbench()->get_request_params()['data'])){
					if (is_array($this->get_workbench()->get_request_params()['data']['rows'])){
						$rows = $this->get_workbench()->get_request_params()['data']['rows'];
						// If there is only one row and it has a UID column, check if the only UID cell has a concatennated value
						if (count($rows) == 1){
							$rows = $this->split_rows_by_multivalue_fields($rows);
						}
						$data_sheet->add_rows($rows);
					}
				} 
			}
			$this->request_data_sheet = $data_sheet;
		}
		
		return $this->request_data_sheet;
	}
	
	protected function split_rows_by_multivalue_fields(array $rows){
		// If there is only one row and it has a UID column, check if the only UID cell has a concatennated value
		$result = $rows;
		if (count($rows) == 1){
			$row = reset($rows);
			foreach ($row as $field => $val){
				if (is_array($val)){
					foreach ($val as $i => $v){
						foreach ($rows as $nr => $r){
							$new_nr = $nr+($nr*$i+$i);
							$result[$new_nr] = $r;
							$result[$new_nr][$field] = $v;
						}
					}
				}
			}
		}
		return $result;
	}
	
	protected function split_concatennated_uid_value($string){
		return explode(EXF_LIST_SEPARATOR, $string);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \exface\Core\CommonLogic\AbstractTemplate::process_request()
	 */
	public function process_request($page_id=NULL, $widget_id=NULL, $action_alias=NULL, $disable_error_handling=false){
		// Look for basic request parameters
		$called_in_resource_id = $page_id ? $page_id : $this->get_request_page_id();
		$called_by_widget_id = $widget_id ? $widget_id : $this->get_request_widget_id();
		$action_alias = $action_alias ? $action_alias : $this->get_request_action_alias();

		$object_id = $this->get_request_object_id();
		if ($this->get_request_id()) $this->get_workbench()->set_request_id($this->get_request_id());
		
		// Remove system variables from the request. These are ones a tempalte always adds to the request for it's own needs.
		// They should be defined in the init() method of the template
		foreach($this->get_request_system_vars() as $var){
			$this->get_workbench()->remove_request_param($var);
		}
		
		// Do the actual processing
		try {
			if ($called_in_resource_id){
				if ($called_by_widget_id){
					$widget = $this->get_workbench()->ui()->get_widget($called_by_widget_id, $called_in_resource_id);
				} else {
					$widget = $this->get_workbench()->ui()->get_page($called_in_resource_id)->get_widget_root();
				}
				if (!$object_id) $object_id = $widget->get_meta_object()->get_id();
				if ($widget instanceof iTriggerAction && (!$action_alias || strtolower($action_alias) == strtolower($widget->get_action()->get_alias_with_namespace()))){
					$action = $widget->get_action();
				}
			}
			
			if (!$action){
				$exface = $this->get_workbench();
				$action = ActionFactory::create_from_string($exface, $action_alias, ($widget ? $widget : null));
			}
			
			// Give 
			$action->set_template_alias($this->get_alias_with_namespace());
			
			// See if the widget needs to be prefilled
			if ($action->implements_interface('iUsePrefillData') && $prefill_data = $this->get_request_prefill_data($widget)){
				$action->set_prefill_data_sheet($prefill_data);
			}
		
			if (!$action){
				throw new TemplateRequestParsingError('Action not specified in request!', '6T6HSAO');
			}
			
			// Read the input data from the request
			$data_sheet = $this->get_data_sheet_from_request($object_id, $widget);
			if ($data_sheet){
				if ($action->get_input_data_sheet()){
					$action->get_input_data_sheet()->import_rows($data_sheet);
				} else {
					$action->set_input_data_sheet($data_sheet);
				}
			}
			// Check, if the action has a widget. If not, give it the widget from the request
			if ($action->implements_interface('iShowWidget') && !$action->get_widget() && $widget){
				$action->set_widget($widget);
			}
			
			$this->set_response_from_action($action);
			
		} catch (ErrorExceptionInterface $e){
			if (!$disable_error_handling){
				$ui = $this->get_workbench()->ui();
				$this->set_response_from_error($e, UiPageFactory::create($ui, 0));
			} else {
				throw $e;
			}
		}

		return $this->get_response();
	}
	
	protected function set_response_from_action(ActionInterface $action){
		$error_msg = null;
		$error_trace = null;
		$warning_msg = null;
		try {
			$output = $action->get_result_output();
		} catch (ErrorExceptionInterface $e){
			if (!$this->get_workbench()->get_config()->get_option('DEBUG.DISABLE_TEMPLATE_ERROR_HANDLERS')){
				try {
					$this->set_response_from_error($e, $action->get_called_on_ui_page());
					return;
				} catch (\Throwable $error_widget_exception){
					// If anything goes wrong when trying to prettify the original error, drop prettifying
					// and just throw the original
					throw $e;
				} catch (FatalThrowableError $error_widget_exception){
					// If anything goes wrong when trying to prettify the original error, drop prettifying
					// and just throw the original
					throw $e;
				}
			} else {
				throw $e;
			}
		} catch (WarningExceptionInterface $w){
			$warning_msg = $w->getMessage();
		}
		
		if (!$output && $action->get_result_message()){			
			$response = array();
			if ($error_msg || $warning_msg){
				$response['error'] = $error_msg;
				$response['warning'] = $warning_msg;
			} else {
				$response['success'] = $action->get_result_message();
				if ($action->is_undoable()){
					$response['undoable'] = '1';
				}
			}
			// Encode the response object to JSON converting <, > and " to HEX-values (e.g. \u003C). Without that conversion
			// there might be trouble with HTML in the responses (e.g. jEasyUI will break it when parsing the response)
			$output = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_QUOT);
		}
		
		$this->set_response($error_msg ? $error_msg . "\n" . $error_trace : $output);
		return $this;
	} 
	
	protected function set_response_from_error(ErrorExceptionInterface $exception, UiPageInterface $page){
		$debug_widget = $exception->create_widget($page);
		$http_status_code = is_numeric($exception->get_status_code()) ? $exception->get_status_code() : 500;
		$output = str_replace(array('[[', '{{'), array('[ [', '{ {'), $this->draw($debug_widget));
		if (is_numeric($http_status_code)){
			http_response_code($http_status_code);
		} else {
			http_response_code(500);
		}
		$this->set_response($output);
		return $this;
	}
	
	/**
	 * Returns the prefill data from the request or FALSE if no prefill data was sent
	 * @param AbstractWidget $widget_to_prefill
	 * @return DataSheetInterface | boolean
	 */
	public function get_request_prefill_data(AbstractWidget $widget_to_prefill){	
		// Look for prefill data
		$prefill_string = $this->get_workbench()->get_request_params()['prefill'];
		if ($prefill_string && $prefill_uxon = UxonObject::from_anything($prefill_string)){
			$exface = $this->get_workbench();
			if (!$prefill_uxon->get_property('meta_object_id') && $prefill_uxon->get_property('oId')){
				$prefill_uxon->set_property('meta_object_id', $prefill_uxon->get_property('oId'));
			}
			$prefill_data = DataSheetFactory::create_from_uxon($exface, $prefill_uxon);
			$this->get_workbench()->remove_request_param('prefill');
			
			if ($prefill_data){
				// Add columns to be prefilled to the data sheet from the request
				$prefill_data = $widget_to_prefill->prepare_data_sheet_to_prefill($prefill_data);
				// If new colums are added, the sheet is marked as outdated, so we need to fetch the data from the data source
				if (!$prefill_data->is_fresh()){
					$prefill_data->add_filter_in_from_string($prefill_data->get_meta_object()->get_uid_alias(), $prefill_data->get_column_values($prefill_data->get_meta_object()->get_uid_alias()));
					$prefill_data->data_read();
				}
				
				$this->request_prefill_data = $prefill_data;
			} else {
				$this->request_prefill_data = false;
			}
		}
		
		// It is important to save the prefill data sheet in the request, because multiple action can be performed in one request
		// and they all will need the prefill data, not just the first one.
		return $this->request_prefill_data;
	}
	
	/**
	 * Returns an array of key-value-pairs for filters contained in the current HTTP request (e.g. [ "DATE_FROM" => ">01.01.2010", "LABEL" => "axenox", ... ]
	 * @return array
	 */
	public function get_request_filters(){
		// Filters a passed as request values with a special prefix: fltr01_, fltr02_, etc.
		if (count($this->request_filters_array) == 0){
			foreach($this->get_workbench()->get_request_params() as $var => $val){
				if (strpos($var, 'fltr') === 0){
					$this->request_filters_array[substr($var, 7)][] = urldecode($val);
					$this->get_workbench()->remove_request_param($var);
				} 
			}
		}
		return $this->request_filters_array;
	}
	
	public function get_request_quick_search_value(){
		if (!$this->request_quick_search_value){
			$this->request_quick_search_value = !is_null($this->get_workbench()->get_request_params()['q']) ? $this->get_workbench()->get_request_params()['q'] : NULL;
			$this->get_workbench()->remove_request_param('q');
		}
		return $this->request_quick_search_value;
	}
	
	public function get_class_prefix() {
		return $this->class_prefix;
	}
	
	public function set_class_prefix($value) {
		$this->class_prefix = $value;
		return $this;
	}
	
	public function get_class_namespace() {
		return $this->class_namespace;
	}
	
	public function set_class_namespace($value) {
		$this->class_namespace = $value;
	}
	
	public function get_request_paging_rows(){
		if (!$this->request_paging_rows){
			$this->request_paging_rows = !is_null($this->get_workbench()->get_request_params()['rows']) ? intval($this->get_workbench()->get_request_params()['rows']) : 0;
			$this->get_workbench()->remove_request_param('rows');
		}
		return $this->request_paging_rows;
	}
	
	public function get_request_sorting_sort_by(){
		if (!$this->request_sorting_sort_by){
			$this->request_sorting_sort_by = !is_null($this->get_workbench()->get_request_param('sort')) ? strval($this->get_workbench()->get_request_param('sort')) : '';
			$this->get_workbench()->remove_request_param('sort');
		}
		return $this->request_sorting_sort_by;
	}
	
	public function get_request_sorting_direction(){
		if (!$this->request_sorting_direction){
			$this->request_sorting_direction = !is_null($this->get_workbench()->get_request_param('order')) ? strval($this->get_workbench()->get_request_param('order')) : '';
			$this->get_workbench()->remove_request_param('order');
		}
		return $this->request_sorting_direction;
	}
	
	public function get_request_paging_offset(){
		if (!$this->request_paging_offset){
			$page = !is_null($this->get_workbench()->get_request_params()['page']) ? intval($this->get_workbench()->get_request_params()['page']) : 1;
			$this->get_workbench()->remove_request_param('page');
			$this->request_paging_offset = ($page-1)*$this->get_request_paging_rows();
		}
		return $this->request_paging_offset;
	}
	
	public function get_request_object_id(){
		if (!$this->request_object_id){
			$this->request_object_id =  !is_null($this->get_workbench()->get_request_params()['object']) ? $this->get_workbench()->get_request_params()['object'] : $_POST['data']['oId'];
			$this->get_workbench()->remove_request_param('object');
		}
		return $this->request_object_id;
	}
	
	public function get_request_page_id(){
		if (!$this->request_page_id){
			$this->request_page_id = !is_null($this->get_workbench()->get_request_params()['resource']) ? intval($this->get_workbench()->get_request_params()['resource']) : NULL;
			$this->get_workbench()->remove_request_param('resource');
		}
		return $this->request_page_id;
	}
	
	public function get_request_widget_id(){
		if (!$this->request_widget_id){
			$this->request_widget_id = !is_null($this->get_workbench()->get_request_params()['element']) ? urldecode($this->get_workbench()->get_request_params()['element']) : '';
			$this->get_workbench()->remove_request_param('element');
		}
		return $this->request_widget_id;
	}
	
	public function get_request_action_alias(){
		if (!$this->request_action_alias){
			$this->request_action_alias = urldecode($this->get_workbench()->get_request_params()['action']);
			$this->get_workbench()->remove_request_param('action');
		}
		return $this->request_action_alias;
	}
	
	public function get_request_system_vars() {
		return $this->request_system_vars;
	}
	
	public function set_request_system_vars(array $var_names) {
		$this->request_system_vars = $var_names;
		return $this;
	}  
	
	public function get_request_id() {
		if (!$this->request_id){
			$this->request_id = urldecode($this->get_workbench()->get_request_params()['exfrid']);
			$this->get_workbench()->remove_request_param('exfrid');
		}
		return $this->request_id;
	}	
	
	public function encode_data($serializable_data){
		return json_encode($serializable_data);
	}
}
?>