<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Interfaces\Widgets\iHaveToolbars;

/**
 * TODO use template elements for Toolbar and ButtonGroup instead of this trait.
 * 
 * @method iHaveToolbars getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait JqueryToolbarsTrait 
{
    /** @var MenuButton */
    private $more_buttons_menu = null;
    
    /**
     * Returns a MenuButton to house buttons that did not fit into the main toolbar.
     * 
     * @param string $caption
     * @param string $icon
     * @return \exface\AbstractAjaxTemplate\Template\Elements\MenuButton
     */
    protected function getMoreButtonsMenu($caption = null, $icon = null)
    {
        $caption = is_null($caption) ? $this->getMoreButtonsMenuCaption() : $caption;
        $icon = is_null($icon) ? $this->getMoreButtonsMenuIcon() : $icon;
        
        $widget = $this->getWidget();
        if (is_null($this->more_buttons_menu)){
            $this->more_buttons_menu = $widget->getPage()->createWidget('MenuButton', $widget);
            $this->more_buttons_menu->setCaption($caption);
            if (! $icon){
                $this->more_buttons_menu->setHideButtonIcon(true);
            } else {
                $this->more_buttons_menu->setIconName($icon);
            }
        }
        return $this->more_buttons_menu;
    }
    
    /**
     * 
     * @return string
     */
    protected function buildHtmlButtons(){
        $button_html = '';
        $widget = $this->getWidget();
        $more_buttons_menu = $this->getMoreButtonsMenu();
        
        if ($widget->hasButtons()) {
            foreach ($widget->getToolbarMain()->getButtonGroups() as $btn_group){
                if ($btn_group->getVisibility() === EXF_WIDGET_VISIBILITY_OPTIONAL){
                    $more_buttons_menu->getMenu()->addButtonGroup($btn_group);
                } else {
                    $button_html .= '<div style="' . ($btn_group->getAlign() === EXF_ALIGN_RIGHT || $btn_group->getAlign() === EXF_ALIGN_OPPOSITE ? 'float: right' : 'float: left') . '" class="exf-btn-group">';
                    
                    foreach ($btn_group->getButtons() as $button) {
                        // Make pomoted and regular buttons visible right in the bottom toolbar
                        // Hidden buttons also go here, because it does not make sense to put them into the menu
                        if ($button->getVisibility() !== EXF_WIDGET_VISIBILITY_OPTIONAL || $button->isHidden()) {
                            $button_html .= $this->getTemplate()->generateHtml($button);
                        }
                        
                        // Put optional buttons in the menu
                        if ($button->getVisibility() == EXF_WIDGET_VISIBILITY_OPTIONAL && ! $button->isHidden()) {
                            $more_buttons_menu->addButton($button);
                        }
                    }
                    
                    $button_html .= '</div>';
                }
            } 
            
            foreach ($widget->getToolbars() as $toolbar){
                if ($toolbar === $widget->getToolbarMain()){
                    continue;
                }
                foreach ($toolbar->getButtonGroups() as $btn_group){
                    if ($btn_group->hasButtons()){
                        $more_buttons_menu->getMenu()->addButtonGroup($btn_group);
                    }
                }
            }
        }
        
        if ($more_buttons_menu->hasButtons()) {
            $button_html .= $this->getTemplate()->getElement($more_buttons_menu)->generateHtml();
        }
        return $button_html;
    }
    
    /**
     * Returns the caption for the MenuButton with additional buttons.
     *
     * The default is an empty string. Override this method to add a caption to 
     * the MenuButton in a specific template.
     *
     * @return string
     */
    protected function getMoreButtonsMenuCaption(){
        return '';
    }
    
    /**
     * Returns the icon for the MenuButton with additional buttons.
     * 
     * The default is an empty string. Override this method to add an icon to 
     * the MenuButton in a specific template.
     * 
     * @return string
     */
    protected function getMoreButtonsMenuIcon(){
        return '';
    }
    
    /**
     * Returns the JS needed for all buttons in this widget
     * @return string
     */
    protected function buildJsButtons(){
        $output = '';
        foreach ($this->getWidget()->getButtons() as $button) {
            $output .= $this->getTemplate()->generateJs($button);
        }
        return $output;
    }
}
