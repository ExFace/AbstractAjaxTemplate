<?php namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\DataTable;

/**
 * This trait contains common methods for template elements using the jQuery DataTables library.
 * 
 * @see http://www.datatables.net
 * 
 * @method DataTable get_widget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait JqueryDataTablesTrait {
	
	private $row_details_expand_icon = 'fa-plus-square-o';
	private $row_details_collapse_icon = 'fa-minus-square-o';
	
	/**
	 * Returns JS code adding a click-event handler to the expand-cell of each row of a table with row details,
	 * that will create and show an additional row for the details of the clicked row. 
	 * 
	 * The contents of the detail-row will be loaded via POST request.
	 * 
	 * @return string
	 */
	protected function build_js_row_details(){
		$output = '';
		$widget = $this->get_widget();
		
		if ($widget->has_row_details()){
			$output = <<<JS
	// Add event listener for opening and closing details
	$('#{$this->get_id()} tbody').on('click', 'td.details-control', function () {
		var tr = $(this).closest('tr');
		var row = {$this->get_id()}_table.row( tr );
		
		if ( row.child.isShown() ) {
			// This row is already open - close it
			row.child.hide();
			tr.removeClass('shown');
			tr.find('.{$this->get_row_details_collapse_icon()}').removeClass('{$this->get_row_details_collapse_icon()}').addClass('{$this->get_row_details_collapse_icon()}');
			$('#detail'+row.data().id).remove();
			{$this->get_id()}_table.columns.adjust();
		}
		else {
			// Open this row
			row.child('<div id="detail'+row.data().{$widget->get_meta_object()->get_uid_alias()}+'"></div>').show();
			$.ajax({
				url: '{$this->get_ajax_url()}',
				method: 'post',
				data: {
					action: '{$widget->get_row_details_action()}',
					resource: '{$this->get_page_id()}',
					element: '{$widget->get_row_details_container()->get_id()}',
					prefill: {
						oId:"{$widget->get_meta_object_id()}",
						rows:[
							{ {$widget->get_meta_object()->get_uid_alias()}: row.data().{$widget->get_meta_object()->get_uid_alias()} }
						],
						filters: {$this->build_js_data_filters()}
					},
					exfrid: row.data().{$widget->get_meta_object()->get_uid_alias()}
				},
				dataType: "html",
				success: function(data){
					$('#detail'+row.data().{$widget->get_meta_object()->get_uid_alias()}).append(data);
					{$this->get_id()}_table.columns.adjust();
				},
				error: function(jqXHR, textStatus, errorThrown ){
					{$this->build_js_show_error('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
				}
			});
			tr.next().addClass('detailRow unselectable');
			tr.addClass('shown');
			tr.find('.{$this->get_row_details_expand_icon()}').removeClass('{$this->get_row_details_expand_icon()}').addClass('{$this->get_row_details_collapse_icon()}');
		}
	} );
JS;
		}
		return $output;
	}
	
	public function get_row_details_expand_icon(){
		return $this->row_details_expand_icon;
	}
	
	public function set_row_details_expand_icon($icon_class){
		$this->row_details_expand_icon = $icon_class;
		return $this;
	}
	
	public function get_row_details_collapse_icon(){
		return $this->row_details_collapse_icon;
	}
	
	public function set_row_details_collapse_icon($icon_class){
		$this->row_details_collapse_icon = $icon_class;
		return $this;
	}
}
?>
