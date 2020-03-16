<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\PHPWiki\WikiPage;

require_once('WikiActions.class.php');
require_once(dirname(__FILE__) . '/../lib/WikiEntry.class.php');
require_once(dirname(__FILE__) . '/../lib/WikiPage.class.php');
require_once(dirname(__FILE__) . '/../lib/WikiAttachment.class.php');
require_once(dirname(__FILE__) . '/../lib/Wiki.class.php');


class WikiServiceAdminActions extends WikiActions
{
  /**
   * @access private
   * @var int
   */
    public $gid;

    public function __construct($controler)
    {
        parent::__construct($controler);
        $this->gid = (int) $controler->gid;
    }

    public function checkPage($page)
    {
        global $feedback;

      /**
       * Check if the given page name is not empty
       */
        if (empty($page)) {
            $feedback = $GLOBALS['Language']->getText('wiki_actions_wikiserviceadmin', 'page_name_empty_err');
            return false;
        }

      /**
       * Check if the page is a valid page.
       */
        $wp = new WikiPage($this->gid, $page);
        if (! $wp->exist()) {
            $wpw = new WikiPageWrapper($this->gid);
            $wpw->addNewProjectPage($page);
        }

        return true;
    }


    public function create()
    {
        $page = $_POST['page'];
        if (!empty($_POST['upage'])) {
            $page = $_POST['upage'];
        }

        if ($this->checkPage($page)) {
            $we = new WikiEntry();
            $we->setGid($this->gid);
            $we->setName($_POST['name']);
            $we->setPage($page);
            $we->setDesc($_POST['desc']);
            $we->setRank($_POST['rank']);
            $we->setLanguage_id($_POST['language_id']);

            $we->add();
        }
    }

    public function delete()
    {
        $we = new WikiEntry();
        $we->setGid($this->gid);
        $we->setId($_REQUEST['id']);

        $we->del();
    }

  /**
   * Perform wiki attachment removal.
   */
    public function deleteAttachments()
    {
        $request = HTTPRequest::instance();
        if ($request->isPost() && $request->exist('attachments_to_delete')) {
            $args = $request->get('attachments_to_delete');
            $deleteStatus = true;
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
            foreach ($args as $id) {
                $valid = new Valid_UInt('repo_id');
                $valid->required();
                if ($valid->validate($id)) {
                    $wa = new WikiAttachment();
                    $wa->initWithId($id);
                    if ($wa->validate() && $wa->gid == $_REQUEST['group_id'] && $wa->isAutorized($user->getId())) {
                        if (!$wa->deleteAttachment()) {
                            $deleteStatus = false;
                        }
                    } else {
                        $deleteStatus = false;
                    }
                } else {
                    $deleteStatus = false;
                }
            }
            if ($deleteStatus) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('wiki_actions_wikiserviceadmin', 'delete_attachment_success'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('wiki_actions_wikiserviceadmin', 'delete_attachment_failure'));
            }
        }
    }

    public function update()
    {
        $page = $_POST['page'];
        if (!empty($_POST['upage'])) {
            $page = $_POST['upage'];
        }

        if ($this->checkPage($page)) {
            $we = new WikiEntry();
            $we->setId($_POST['id']);
            $we->setGid($this->gid);
            $we->setName($_POST['name']);
            $we->setPage($page);
            $we->setDesc($_POST['desc']);
            $we->setRank($_POST['rank']);
            $we->setLanguage_id($_POST['language_id']);

            $we->update();
        }
    }

    public function setWikiPerms()
    {
        global $feedback;

        $w = new Wiki($this->gid);
        if ($_POST['reset']) {
            $ret = $w->resetPermissions();
        } else {
            $ret = $w->setPermissions($_POST['ugroups']);
        }

        if (!$ret) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText('wiki_actions_wikiserviceadmin', 'update_perm_err', array($feedback))
            );
        }

        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            "wiki_service_permissions_updated",
            array(
            'group_id' => $this->gid
            )
        );
    }

    public function setWikiPagePerms()
    {
        global $feedback;

        $wp = new WikiPage($_POST['object_id']);

        if ($_POST['reset']) {
            $ret = $wp->resetPermissions();
        } else {
            $ret = $wp->setPermissions($_POST['ugroups']);
        }

        if (!$ret) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText(
                    'wiki_actions_wikiserviceadmin',
                    'update_page_perm_err',
                    array($feedback)
                )
            );
        }

        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            "wiki_page_permissions_updated",
            array(
            'group_id'         => $wp->getGid(),
            'wiki_page'        => $wp->getPagename(),
            )
        );
    }


    /**
     * Wrapper to set permissions on wiki attachments.
     */
    public function setWikiAttachmentPerms()
    {
        global $feedback;

        $wa = new WikiAttachment($this->gid);
        $wa->initWithId($_POST['object_id']);
        if ($_POST['reset']) {
            $ret = $wa->resetPermissions();
        } else {
            $ret = $wa->setPermissions($_POST['ugroups']);
        }
        if (!$ret) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText('wiki_actions_wikiserviceadmin', 'update_attachment_perm_err', array($feedback))
            );
        }
    }
}
