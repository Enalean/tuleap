<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 */

use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionOngoingUploadRetriever;

class Docman_View_ItemDetailsSectionNewVersion extends Docman_View_ItemDetailsSectionActions
{

    public $force;
    public $token;
    public function __construct($item, $url, $controller, $force, $token)
    {
        parent::__construct($item, $url, false, true, $controller);
        $this->force    = $force;
        $this->token = $token;
    }
    public function getContent($params = [])
    {
        return $this->item->accept($this);
    }

    public function _getApprovalTable()
    {
        $html = '';

        $atf = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item);
        if ($atf->tableExistsForItem()) {
            $html .= '<dt>' . dgettext('tuleap-docman', 'Approval table') . '</dt><dd>';
            $html .= '<dd>';
            $html .= Docman_View_ItemDetailsSectionApprovalCreate::displayImportLastTable(false);
            $html .= '</dd>';
        }

        return $html;
    }

    public function _getReleaseLock()
    {
        $content = '';
        $dPm = Docman_PermissionsManager::instance($this->item->getGroupId());
        if ($dPm->getLockFactory()->itemIsLocked($this->item)) {
            $content .= '<tr style="vertical-align:top;">';
            $content .= '<td><label>' . dgettext('tuleap-docman', 'Keep the lock:') . '</label></td>';
            $content .= '<td><input type="checkbox" name="lock_document" value="lock" /></td>';
            $content .= '</tr>';
        }
        return $content;
    }

    public function visitFolder($item, $params = array())
    {
        return "";
    }
    public function visitDocument($item, $params = array())
    {
        return "";
    }
    public function visitWiki($item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink($item, $params = array())
    {
        return $this->visitVersionnedItem($item, $params);
    }

    public function visitFile($item, $params = array())
    {
        return $this->visitVersionnedItem($item, $params);
    }

    private function visitVersionnedItem($item, $params = array())
    {
        $label = '';
        if (isset($this->_controller->_viewParams['label'])) {
            $label = $this->_controller->_viewParams['label'];
        }
        $changelog = '';
        if (isset($this->_controller->_viewParams['changelog'])) {
            $changelog = $this->_controller->_viewParams['changelog'];
        }

        $retriever = new VersionOngoingUploadRetriever(new DocumentOnGoingVersionToUploadDAO());

        if ($retriever->isThereAlreadyAnUploadOngoing($item, new DateTimeImmutable())) {
            $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../../templates");
            return $renderer->renderToString(
                'document_file_upload_on_going_error',
                []
            );
        }
        $content = '';
        $content .= '<form action="' . $this->url . '&amp;id=' . $this->item->getId() . '" method="post" enctype="multipart/form-data" id="plugin_docman_new_version_form" data-test="plugin_docman_new_version_form">';

        $content .= '<dl>';
        $content .= '<dt>' . dgettext('tuleap-docman', 'Update') . '</dt><dd>';
        $content .= '<table>';
        $content .= '<tr style="vertical-align:top"><td>' . dgettext('tuleap-docman', 'Version Label:') . '</td><td><input type="text" name="version[label]" value="' . $label . '" /></td></tr>';
        $content .= '<tr style="vertical-align:top"><td>' . dgettext('tuleap-docman', 'Change Log:') . '</td><td><textarea name="version[changelog]" rows="7" cols="80" data-test="docman_changelog">' . $changelog . '</textarea></td></tr>';
        $fields = $item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('force_item' => $this->force, 'request' => $this->_controller->request));
        if ($fields !== null) {
            foreach ($fields as $field) {
                $content .= '<tr style="vertical-align:top;">';
                $content .= '<td><label>' . $field->getLabel() . '</label></td>';
                $content .= '<td>' . $field->getField() . '</td></tr>';
            }
        }
        // Release lock
        $content .= $this->_getReleaseLock();

        $content .= '</table>';
        $content .= '</dd>';

        $content .= $this->_getApprovalTable();

        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="' . $this->token . '" />';
        }
        $content .= '<input type="hidden" name="action" value="new_version" />';
        $content .= '<input type="submit" name="confirm" data-test="docman_create_new_version" value="' . dgettext('tuleap-docman', 'Create new version') . '" />';
        $content .= '<input type="submit" name="cancel"  value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';
        $content .= '</p>';

        $content .= '</dl>';
        $content .= '</form>';
        $snippet = '
        document.observe("dom:loaded", function () {
            $("plugin_docman_new_version_form").observe("submit", function (e) {
                if (!docman.approvalTableCheck($("plugin_docman_new_version_form"))) {
                    e.stop();
                }
            });
        });
        ';
        $GLOBALS['Response']->includeFooterJavascriptSnippet($snippet);
        return $content;
    }

    public function visitEmbeddedFile($item, $params = array())
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty($item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
