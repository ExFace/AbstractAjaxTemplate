<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\Form;

/**
 *
 * @method Form get_widget()
 *        
 * @author Andrej Kabachnik
 *        
 */
trait JqueryFormTrait {

    function buildHtmlButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getTemplate()->generateHtml($btn);
        }
        
        return $output;
    }

    function buildJsButtons()
    {
        $output = '';
        foreach ($this->getWidget()->getButtons() as $btn) {
            $output .= $this->getTemplate()->generateJs($btn);
        }
        
        return $output;
    }
}
?>