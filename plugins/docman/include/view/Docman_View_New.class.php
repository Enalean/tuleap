<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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
 */

abstract class Docman_View_New extends Docman_View_Display /* implements Visitor */
{
    public $newItem;

    /* protected abstract */ public function _getEnctype()
    {
    }

    /* protected abstract */ public function _getAction()
    {
    }

    /* protected abstract */ public function _getActionText()
    {
    }

    /* protected abstract */ public function _getForm()
    {
    }

    /* protected */ public function _getSpecificProperties($params)
    {
        return '';
    }

    /* protected */ public function _getCategories($params)
    {
        return '';
    }
    /* protected */ public function _getJSDocmanParameters($params)
    {
        $doc_params = array();
        if (isset($params['force_permissions'])) {
            $doc_params['newItem'] = array(
               'hide_permissions'           => !$params['display_permissions'],
                'hide_news'                  => !$params['display_news'],
                'update_permissions_on_init' => false,
                'default_position'           => $params['force_ordering']
            );
        }
        return array_merge(
            parent::_getJSDocmanParameters($params),
            $doc_params
        );
    }

    public function _getPropertiesFieldsDisplay($fields)
    {
        $html = '';
        $html .= '<table>';
        foreach ($fields as $field) {
            $html .= '<tr>';
            $html .= '<td>' . $field->getLabel() . '</td>';
            $html .= '<td>' . $field->getField() . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function metadataToSkip()
    {
        $labels = array('owner'       => 'owner',
                        'create_date' => 'create_date',
                        'update_date' => 'update_date');
        return $labels;
    }

    public function _getNewItem()
    {
        return null;
    }

    public function setupNewItem($params)
    {
        $mdFactory = new Docman_MetadataFactory($params['group_id']);

        if (isset($params['force_item'])) {
            $this->newItem = $params['force_item'];
        } else {
            $this->newItem = $this->_getNewItem();
            $this->newItem->setParentId($params['item']->getId());
            $this->newItem->setGroupId($params['group_id']);
            $mdFactory->appendItemMetadataList($this->newItem);

            // Get default values
            $mdFactory->appendDefaultValuesToItem($this->newItem);
        }

        // Append, for list Metadata the list of values associated in the DB
        // (content of select box)
        $mdFactory->appendAllListOfValuesToItem($this->newItem);
    }

    public function _getPropertiesFields($params)
    {
        $get_fields = new Docman_View_GetFieldsVisitor($this->metadataToSkip());
        $fields = $this->newItem->accept($get_fields, array('form_name'  => $params['form_name'],
                                                            'theme_path' => $params['theme_path']));
        return $fields;
    }

    public function _getGeneralProperties($params)
    {
        $html = '';
        $fields = $this->_getPropertiesFields($params);
        $html .= $this->_getPropertiesFieldsDisplay($fields);
        $html .= '<input type="hidden" name="item[parent_id]" value="' . $this->newItem->getParentId() . '" />';
        return $html;
    }

    public function _getGeneralPropertiesFieldset($params)
    {
        $html = '';
        $html .= '<div class="properties">' . "\n";
        $html .= '<h3>' . dgettext('tuleap-docman', 'Properties') . '</h3>';
        $html .= $this->_getGeneralProperties($params);
        $html .= '<p><span class="highlight">' . dgettext('tuleap-docman', '* Mandatory field') . '</span></p>';
        $html .= '<input type="hidden" name="action" value="' . $this->_getAction() . '" />';
        $html .= '</div>';
        return $html;
    }

    public function _getDefaultValuesFieldset($params)
    {
        return '';
    }

    public function _getSpecificPropertiesFieldset($params)
    {
        $html = '';
        $html .= '<h3>' . dgettext('tuleap-docman', 'Document type') . '</h3>';
        $html .= $this->_getSpecificProperties($params);
        return $html;
    }

    public function _getLocationFieldset($params)
    {
        $html = '';
        $html .= '<h3>' . dgettext('tuleap-docman', 'Location') . '</h3>';
        $itemRanking = new Docman_View_ItemRanking();

        if (isset($params['ordering'])) {
            $itemRanking->setSelectedValue($params['ordering']);
            $itemRanking->setDropDownName('ordering');
        }

        $html .= $itemRanking->getDropDownWidget($params['item']);
        return $html;
    }

    public function _getPermissionsFieldset($params)
    {
        $html = '';
        $html .= '<h3>Permissions</h3>';
        $html .= '<div id="docman_new_permissions_panel">';
        $p = new Docman_View_PermissionsForItem($this->_controller);
        $params['user_can_manage'] = $this->_controller->userCanWrite($this->newItem->getParentId());
        $html .= $p->fetch($this->newItem->getParentId(), $params);
        $html .= '</div>';
        return $html;
    }

    public function _getNewsFieldset($params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $user = $this->_controller->getUser();
        if ($user->isMember($params['item']->getGroupId(), 'A') || $user->isMember($params['item']->getGroupId(), 'N1') || $user->isMember($params['item']->getGroupId(), 'N2')) {
            $default_news_summary = '';
            $default_news_details = '';
            $default_news_private_check = '';
            $default_news_public_check = 'checked="checked"';
            if (isset($params['force_news'])) {
                $default_news_summary = isset($params['force_news']['summary']) ? $params['force_news']['summary'] : $default_news_summary;
                $default_news_details = isset($params['force_news']['details']) ? $params['force_news']['details'] : $default_news_details;
                if (isset($params['force_news']['is_private']) && $params['force_news']['is_private']) {
                    $default_news_private_check = $default_news_public_check;
                    $default_news_public_check = '';
                }
            }
            $html .= '<h3>News</h3>';
            $html .= '<div id="docman_new_news_panel">';

            $html .= '<p>' . dgettext('tuleap-docman', 'Let fields blank if you do not want news.') . '</p>';

            $html .= '<div>';
            $html .= '<b><label for="news_summary">' . $GLOBALS['Language']->getText('news_admin_index', 'subject') . ':</label></b><br />';
            $html .= '<input type="text" name="news[summary]" id="news_summary" value="' .  $hp->purify($default_news_summary, CODENDI_PURIFIER_CONVERT_HTML)  . '" size="44" maxlength="60" /><br />';
            $html .= '</div>';

            $html .= '<div>';
            $html .= '<b><label for="news_details">' . $GLOBALS['Language']->getText('news_admin_index', 'details') . ':</label></b><br />';
            $html .= '<textarea name="news[details]" rows="8" cols="50" wrap="soft">' .  $hp->purify($default_news_details, CODENDI_PURIFIER_CONVERT_HTML)  . '</textarea><br />';
            $html .= '</div>';

            $html .= '<table><tr style="vertical-align:top"><td><b>' . $GLOBALS['Language']->getText('news_submit', 'news_privacy') . '</b></td><td>';
            $html .= '<input type="radio" name="news[is_private]" id="news_is_private_no" value="0" ' . $default_news_public_check . ' />';
            $html .= '<label class="docman-create-news-option" for="news_is_private_no">' . $GLOBALS['Language']->getText('news_submit', 'public_news') . '</label><br />';
            $html .= '<input type="radio" name="news[is_private]" id="news_is_private_yes" value="1" ' . $default_news_private_check . ' />';
            $html .= '<label class="docman-create-news-option" for="news_is_private_yes">' . $GLOBALS['Language']->getText('news_submit', 'private_news') . '</label>';
            $html .= '</td></tr></table>';

            $html .= '</div>';
        }
        return $html;
    }

    public function _content($params)
    {
        $params['form_name'] = 'new_item';

        $this->setupNewItem($params);

        $html  = '';
        $html .= '<form name="' . $params['form_name'] . '" data-test="docman_new_form" id="docman_new_form" action="' . $params['default_url'] . '" method="POST" ' . $this->_getEnctype() . ' class="docman_form">';

        $html .= '<div class="docman_new_item" data-test="docman_new_item">' . "\n";

        $html .= $this->_getGeneralPropertiesFieldset($params);
        $html .= $this->_getDefaultValuesFieldset($params);
        $html .= $this->_getSpecificPropertiesFieldset($params);
        $html .= $this->_getLocationFieldset($params);
        $html .= $this->_getPermissionsFieldset($params);
        $html .= $this->_getNewsFieldset($params);

        $html .= '<div class="docman_new_submit">' . "\n";
        if (isset($params['token']) && $params['token']) {
            $html .= '<input type="hidden" name="token" value="' . $params['token'] . '" />';
        }
        $html .= '<input type="submit" data-test="docman_create" value="' . $this->_getActionText() . '" />';
        $html .= '<input type="submit" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';
        $html .= '</div>' . "\n";

        $html .= '</div>' . "\n";

        $html .= '</form>';
        $html .= '<br />';
        echo $html;
    }
}
