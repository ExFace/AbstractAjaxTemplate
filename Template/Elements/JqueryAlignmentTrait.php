<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

trait JqueryAlignmentTrait {

    /**
     * Calculates the value of the CSS attribute text-align based on the align property of the widget.
     * 
     * @return string
     */
    public function buildCssTextAlignValue()
    {
        $align = $this->getWidget()->getAlign();
        
        if ($align === EXF_ALIGN_DEFAULT || $align === EXF_ALIGN_OPPOSITE){
            $input_element = $this->getInputElement();
            
            if (method_exists($input_element, 'getDefaultButtonAlignment')){
                $default_alignment = $input_element->getDefaultButtonAlignment();
            } else {
                $default_alignment = $this->getTemplate()->getConfig()->getOption('WIDGET.ALL.DEFAULT_BUTTON_ALIGNMENT');
            }
            
            if ($align === EXF_ALIGN_DEFAULT){
                return $default_alignment;
            } elseif ($default_alignment === EXF_ALIGN_LEFT){
                return EXF_ALIGN_RIGHT;
            } else {
                return EXF_ALING_LEFT;
            }
        }
        
        return $align;
    }
}
?>