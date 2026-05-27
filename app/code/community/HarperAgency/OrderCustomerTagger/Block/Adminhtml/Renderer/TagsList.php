<?php
/**
 * HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_TagsList
 *
 * Renders tag badges inside the Orders or Customers admin grid.
 * The column is added via the core_block_abstract_prepare_layout_after observer;
 * this renderer does a single lazy-loaded query per row.
 *
 * Usage in _prepareColumns():
 *   'renderer' => 'HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_TagsList',
 *   'extra'    => array('entity' => 'order')   // or 'customer'
 */
class HarperAgency_OrderCustomerTagger_Block_Adminhtml_Renderer_TagsList
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $entityType = $this->getColumn()->getData('entity_type') ?: 'order';
        $entityId   = (int) $row->getId();

        if ($entityId <= 0) {
            return '—';
        }

        $tags = Mage::helper('harper_tagger')->getTagsForEntity($entityType, $entityId);

        if (empty($tags)) {
            return '<span style="color:#aaa;">—</span>';
        }

        $html = '';
        foreach ($tags as $tag) {
            $name  = htmlspecialchars((string) $tag->getName(), ENT_QUOTES, 'UTF-8');
            $color = htmlspecialchars((string) $tag->getColor(), ENT_QUOTES, 'UTF-8');
            $color = preg_match('/^#[0-9a-f]{3,6}$/i', $color) ? $color : '#3498db';

            // Luminance-based text colour (same as Badge renderer)
            $textColor = $this->_getTextColor($color);

            $imageUrl = (string) $tag->getImageUrl();

            if ($imageUrl) {
                $imgSrc = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
                $html  .= '<span style="display:inline-flex;align-items:center;background:'
                       . $color . ';color:' . $textColor
                       . ';padding:2px 7px;border-radius:3px;margin:1px 2px;font-size:11px;white-space:nowrap;">'
                       . '<img src="' . $imgSrc . '" alt="' . $name . '" '
                       . 'style="height:14px;width:14px;object-fit:cover;margin-right:4px;border-radius:2px;" />'
                       . $name . '</span>';
            } else {
                $html .= '<span style="display:inline-block;background:' . $color . ';color:' . $textColor
                       . ';padding:2px 7px;border-radius:3px;margin:1px 2px;font-size:11px;white-space:nowrap;">'
                       . $name . '</span>';
            }
        }

        return $html;
    }

    /**
     * Return #fff or #111 based on perceived luminance of a hex color.
     */
    protected function _getTextColor($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return '#111111';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.55 ? '#111111' : '#ffffff';
    }
}
