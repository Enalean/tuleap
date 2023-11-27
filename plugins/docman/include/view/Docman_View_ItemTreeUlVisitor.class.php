<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemTreeUlVisitor implements \Tuleap\Docman\Item\ItemVisitor
{
    public $html;
    public $js;
    public $stripFirstNode;
    public $firstNodeStripped;
    public $docmanIcons;
    public $showOptions;
    public $defaultUrl;
    public $get_action_on_title;
    public $get_class_for_link;
    public $hp;
    /**
     * @var Docman_View_GetActionOnIconVisitor
     */
    private $get_action_on_icon;
    /**
     * @var array
     */
    private $params;

    public function __construct($view, $params = null)
    {
        $this->view               = $view;
        $this->get_action_on_icon = new Docman_View_GetActionOnIconVisitor();
        $this->get_class_for_link = new Docman_View_GetClassForLinkVisitor();
        $this->html               = '';
        $this->js                 = '';
        $this->stripFirstNode     = true;
        $this->firstNodeStripped  = false;
        $this->hp                 = Codendi_HTMLPurifier::instance();
        $this->params             = $params ?? [];
        if (! isset($this->params['default_url'])) {
            $this->params['default_url'] = null;
        }
    }

    public function toHtml()
    {
        return $this->html;
    }

    public function getJavascript()
    {
        return $this->js;
    }

    public function _canDisplayItem($item)
    {
        return true;
    }

    public function _canDisplaySubItems($item)
    {
        return true;
    }

    public function visitFolder(Docman_Folder $item, $params = [])
    {
        $li_displayed = $this->_displayItem($item, $params);
        if ($this->_canDisplaySubItems($item)) {
            $items = $item->getAllItems();
            if ($items) {
                $nb = $items->size();
                if ($nb) {
                    $this->html .= '<ul id="subitems_' . $item->getId() . '" class="docman_items">' . "\n";
                    $i           = 0;
                    $iter        = $items->iterator();
                    $iter->rewind();
                    while ($iter->valid()) {
                        $child = $iter->current();
                        $child->accept($this, ['is_last' => (++$i == $nb)]);
                        $iter->next();
                    }

                    $this->html .= '</ul>' . "\n";
                }
            }
        }

        if ($li_displayed) {
            $this->html .= '</li>' . "\n";
        }
        return '';
    }

    public function visitDocument($item, $params = [])
    {
        $params['popup_doc'] = true;
        $li_displayed        = $this->_displayItem($item, $params);
        if ($li_displayed) {
            $this->html .= '</li>' . "\n";
        }
    }

    public function visitWiki(Docman_Wiki $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink(Docman_Link $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitFile(Docman_File $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        return '';
    }


    //{{{
    public function _displayItem(&$item, $params)
    {
        $li_displayed = false;
        if ($this->stripFirstNode && ! $this->firstNodeStripped) {
            $this->firstNodeStripped = true;
            if (isset($this->params['display_description']) && $this->params['display_description']) {
                $this->html .= '<p>' . $item->getDescription() . '</p>';
            }
        } else {
            if ($item !== null && $this->_canDisplayItem($item)) {
                $this->html        .= '<li id="item_' . $item->getId() . '" class="' . Docman_View_Browse::getItemClasses($params) . '">';
                $params['expanded'] = true;
                $open               = '_open';
                if (! isset($this->params['item_to_move']) && (user_get_preference(PLUGIN_DOCMAN_EXPAND_FOLDER_PREF . '_' . $item->getGroupId() . '_' . $item->getId()) === false)) {
                    $params['expanded'] = false;
                    $open               = '';
                }

                $type     = $item->getType();
                $purifier = Codendi_HTMLPurifier::instance();

                $icon_src = $this->params['docman_icons']->getIconForItem($item, $params);
                $icon     = '<img src="' . $icon_src . '" id="docman_item_icon_' . $purifier->purify($item->getId()) . '" alt="' . $purifier->purify($type) . '" class="docman_item_icon" />';

                $this->html .= '<div>';
                $action      = isset($this->params['item_to_move']) ? false : $item->accept($this->get_action_on_icon, ['view' => &$this->view]);
                if ($action) {
                    $class = $item->accept($this->get_class_for_link, ['view' => &$this->view]);
                    if ($class) {
                        $class .= $open;
                    }
                    $url = DocmanViewURLBuilder::buildActionUrl(
                        $item,
                        $this->params,
                        ['action' => $action, 'id' => $item->getId()]
                    );

                    $this->html .= '<a href="' . $url . '" id="docman_item_link_' . $purifier->purify($item->getId()) . '" class="' . $class . '">';
                }
                $this->html .=  $icon;

                //Display a lock icon for the locked document
                $dpm = Docman_PermissionsManager::instance($item->getGroupId());

                if ($action) {
                    $this->html .= '</a>';
                }
                $this->html .=  '<span class="docman_item_title">';
                if ($action) {
                    $url         = DocmanViewURLBuilder::buildActionUrl(
                        $item,
                        $this->params,
                        ['action' => 'show', 'id' => $item->getId()],
                        false,
                        isset($params['popup_doc'])
                    );
                    $help_window = empty($params['pv']) ? '' : 'data-help-window';
                    $this->html .= '<a ' . $help_window . ' href="' . $url . '" id="docman_item_title_link_' . $purifier->purify($item->getId()) . '">';
                }

                $this->html .=   $this->hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML);
                if ($action) {
                    $this->html .= '</a>';
                }
                $this->html .=  '</span>';

                if ($dpm->getLockFactory()->itemIsLocked($item)) {
                    $lockIconSrc = $this->params['docman_icons']->getIcon('lock_delete.png');
                    $lockIcon    = '<i id="docman_item_icon_locked_' . $purifier->purify($item->getId()) . '"  title="' . dgettext('tuleap-docman', 'Locked document') . '" class="fa fa-lock"></i>';
                    $this->html .=  $lockIcon;
                }
                $this->html .= $this->view->getItemMenu($item, $this->params);
                $this->js   .= $this->view->getActionForItem($item);
                $this->html .= '</div>';

                if (trim($item->getDescription()) != '') {
                    $this->html .= '<div class="docman_item_description">' . $this->hp->purify($item->getDescription(), CODENDI_PURIFIER_BASIC, $item->getGroupId()) . '</div>';
                }
                $li_displayed = true;
            }
        }
        return $li_displayed;
    }
    //}}}
}
