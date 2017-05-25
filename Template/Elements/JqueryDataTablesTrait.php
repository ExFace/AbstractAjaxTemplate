<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\DataTable;

/**
 * This trait contains common methods for template elements using the jQuery DataTables library.
 *
 * @see http://www.datatables.net
 *
 * @method DataTable getWidget()
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
    protected function buildJsRowDetails()
    {
        $output = '';
        $widget = $this->getWidget();
        
        if ($widget->hasRowDetails()) {
            $output = <<<JS
	// Add event listener for opening and closing details
	$('#{$this->getId()} tbody').on('click', 'td.details-control', function () {
		var tr = $(this).closest('tr');
		var row = {$this->getId()}_table.row( tr );
		
		if ( row.child.isShown() ) {
			// This row is already open - close it
			row.child.hide();
			tr.removeClass('shown');
			tr.find('.{$this->getRowDetailsCollapseIcon()}').removeClass('{$this->getRowDetailsCollapseIcon()}').addClass('{$this->getRowDetailsCollapseIcon()}');
			$('#detail'+row.data().id).remove();
			{$this->getId()}_table.columns.adjust();
		}
		else {
			// Open this row
			row.child('<div id="detail'+row.data().{$widget->getMetaObject()->getUidAlias()}+'"></div>').show();
			$.ajax({
				url: '{$this->getAjaxUrl()}',
				method: 'post',
				data: {
					action: '{$widget->getRowDetailsAction()}',
					resource: '{$this->getPageId()}',
					element: '{$widget->getRowDetailsContainer()->getId()}',
					prefill: {
						oId:"{$widget->getMetaObjectId()}",
						rows:[
							{ {$widget->getMetaObject()->getUidAlias()}: row.data().{$widget->getMetaObject()->getUidAlias()} }
						],
						filters: {$this->buildJsDataFilters()}
					},
					exfrid: row.data().{$widget->getMetaObject()->getUidAlias()}
				},
				dataType: "html",
				success: function(data){
					$('#detail'+row.data().{$widget->getMetaObject()->getUidAlias()}).append(data);
					{$this->getId()}_table.columns.adjust();
				},
				error: function(jqXHR, textStatus, errorThrown ){
					{$this->buildJsShowError('jqXHR.responseText', 'jqXHR.status + " " + jqXHR.statusText')}
				}
			});
			tr.next().addClass('detailRow unselectable');
			tr.addClass('shown');
			tr.find('.{$this->getRowDetailsExpandIcon()}').removeClass('{$this->getRowDetailsExpandIcon()}').addClass('{$this->getRowDetailsCollapseIcon()}');
		}
	} );
JS;
        }
        return $output;
    }

    public function getRowDetailsExpandIcon()
    {
        return $this->row_details_expand_icon;
    }

    public function setRowDetailsExpandIcon($icon_class)
    {
        $this->row_details_expand_icon = $icon_class;
        return $this;
    }

    public function getRowDetailsCollapseIcon()
    {
        return $this->row_details_collapse_icon;
    }

    public function setRowDetailsCollapseIcon($icon_class)
    {
        $this->row_details_collapse_icon = $icon_class;
        return $this;
    }
}
?>
