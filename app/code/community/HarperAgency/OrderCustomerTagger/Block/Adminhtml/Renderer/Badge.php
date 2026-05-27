<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_Badge
 *
 * Renders a tag name with a coloured pill badge in the admin grid.
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_Badge
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $name  = $this->htmlEscape((string) $row->getData('name'));
        $color = $this->htmlEscape((string) $row->getData('color'));

        if (!$color) {
            $color = '#3788d8';
        }

        // Determine readable text colour (dark text on light bg, white on dark)
        $textColor = $this->_isLightColor($color) ? '#333333' : '#ffffff';

        $style = sprintf(
            'display:inline-block;padding:2px 10px;border-radius:12px;'
            . 'background-color:%s;color:%s;font-size:11px;font-weight:bold;',
            $color,
            $textColor
        );

        return sprintf('<span style="%s">%s</span>', $style, $name);
    }

    /**
     * Returns true when the hex colour is closer to white than black.
     *
     * @param  string $hex  e.g. '#e67e22'
     * @return bool
     */
    protected function _isLightColor($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return false;
        }
        $r   = hexdec(substr($hex, 0, 2));
        $g   = hexdec(substr($hex, 2, 2));
        $b   = hexdec(substr($hex, 4, 2));
        // Perceived luminance formula
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.55;
    }
}
