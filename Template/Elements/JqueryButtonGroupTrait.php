<?php
namespace exface\AbstractAjaxTemplate\Template\Elements;

use exface\Core\Widgets\MenuButton;
use exface\Core\Widgets\ButtonGroup;

/**
 * This is the reference implementation of the ButtonGroup widget for jQuery templates.
 * 
 * In a nutshell, it is a <div> containing all buttons with normal or promoted
 * visibility one-after-another.
 * 
 * Optional buttons are moved to automatically created menu buttons at the end
 * of the button group (i.e. more-actions-menus). As a side-effect a group
 * consisting entirely of optional buttons will only yield the more-actions-menu.
 * 
 * Note, that if the ButtonGroup itself has visibility=optional and is part of
 * a toolbar, the toolbar will not render it, but move to the more-actions-menu
 * of the preceding visible button group. See JqueryToolbarTrait for details.
 * 
 * @method ButtonGroup getWidget()
 * 
 * @author Andrej Kabachnik
 *
 */
trait JqueryButtonGroupTrait 
{
    use JqueryAlignmentTrait;
    
    /** @var MenuButton */
    private $more_buttons_menu = null;
    
    protected function init()
    {
        // Put optional buttons in the menu
        // NOTE: this separation of optional buttons must be done here because
        // after the button group had been initialized, other groups could add
        // buttons to the menu (see JquerToolbarTrait) and these "external"
        // buttons must be placed after the own ones. Another reason is that
        // buildHtmlButtons will traverse buttons differently depending on the
        // alignment of the group - this should not have an effect on the menu
        // thogh.
        foreach ($this->getWidget()->getButtons() as $button) {      
            if ($button->getVisibility() == EXF_WIDGET_VISIBILITY_OPTIONAL && ! $button->isHidden()) {
                $this->getMoreButtonsMenu()->addButton($button);
            }
        }
    }
    
    /**
     * Returns a MenuButton to house buttons that did not fit into the main toolbar.
     * 
     * TODO An optional button group should be rendered as a MenuButton itself.
     *
     * @return MenuButton
     */
    public function getMoreButtonsMenu()
    {
        $widget = $this->getWidget();
        if (is_null($this->more_buttons_menu)){    
            $icon = $this->getMoreButtonsMenuIcon();
            $this->more_buttons_menu = $widget->getPage()->createWidget('MenuButton', $widget);
            $this->more_buttons_menu->setCaption($this->getMoreButtonsMenuCaption());
            if (! $icon){
                $this->more_buttons_menu->setHideButtonIcon(true);
            } else {
                $this->more_buttons_menu->setIconName($icon);
            }
        }
        return $this->more_buttons_menu;
    }
    
    public function generateHtml()
    {   
        return $this->buildHtmlButtonGroupWrapper($this->buildHtmlButtons());
    }
    
    /**
     * Returns the HTML code including all buttons in this group plus a MenuButton 
     * for buttons with visibility=optional.
     * 
     * @return string
     */
    public function buildHtmlButtons()
    {
        $widget = $this->getWidget(); 
        
        if ($widget->isHidden()){
            return '';
        }
        
        $button_html = '';
        $more_buttons_menu = $this->getMoreButtonsMenu();
        
        if ($widget->hasButtons()){
            $btns = $widget->getButtons();
            if ($this->buildCssTextAlignValue($widget->getAlign()) == 'right'){
                $btns = array_reverse($btns);
            }
            foreach ($btns as $button) {
                // Generate HTML for every button except hidden and optional ones
                // Optional buttons were already placed in the more-buttons-menu in init()
                if ($button->getVisibility() !== EXF_WIDGET_VISIBILITY_OPTIONAL || $button->isHidden()) {
                    $button_html .= $this->getTemplate()->generateHtml($button);
                }
            }
        }
        
        // Add the menu button - even if there were no regular buttons!
        if ($more_buttons_menu->hasButtons()) {
            $button_html .= $this->getTemplate()->getElement($more_buttons_menu)->generateHtml();
        }
        
        return $button_html;
    }
    
    public function buildHtmlButtonGroupWrapper($buttons_html)
    {
        $style = '';
        if ($this->buildCssTextAlignValue($this->getWidget()->getAlign()) == 'right'){
            $style = 'float: right;';
        }
        return '<div style="' . $style . '" class="exf-btn-group">' . $buttons_html . '</div>';
    }
    
    public function generateJs()
    {
        $js = '';
        foreach ($this->getWidget()->getButtons() as $button) {
            $js .= $this->getTemplate()->generateJs($button);
        }
        return $js;
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
}
