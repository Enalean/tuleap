<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Docman_Controller.class.php';
require_once 'Docman_WikiRequest.class.php';
require_once 'Docman_Log.class.php';
require_once 'Docman_ItemDao.class.php';
require_once 'Docman_ItemFactory.class.php';
require_once 'common/wiki/phpwiki/lib/HtmlElement.php';

class Docman_WikiController extends Docman_Controller {

    var $params;

    function __construct(&$plugin, $pluginPath, $themePath, $request) {
        parent::__construct($plugin, $pluginPath, $themePath, $request);
        $event_manager =& $this->_getEventManager();
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE, $this->logger, 'log', true, 0);
        $event_manager->addListener(PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE, $this->notificationsManager, 'somethingHappen', true, 0);
    }

    function request() {
    }

    function viewsManagement() {
    }

    function actionsManagement() {
        switch ($this->request->get('action')) {
            case 'wiki_page_updated':
                $this->wikiPageUpdated();
                break;
            case 'propagate_new_wiki_page_perms':
                $this->propagatePermsToNewWikiPage();
                break;
            case 'wiki_before_content':
                $this->wiki_before_content();
            default:
                break;
        }
    }

    function wikiPageUpdated() {
        $event_manager =& $this->_getEventManager();
        
        $wiki_page = $this->request->get('wiki_page');
        $group_id = $this->request->get('group_id');
        $documents = $this->_getDocmanReferences($wiki_page, $group_id);
        foreach($documents as $key => $document) {
            // Update the item's update date attribute.
            $item_dao =& new Docman_ItemDao(CodexDataAccess::instance());
            $item_dao->updateById($document->getId(), null, null, null, null, null, $update_date=time(), 
                        null, null, null, null, null, null);
            
            $event_manager->processEvent(PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE, array(
                            'group_id'  => $group_id,
                            'item'      => $document,
                            'user'      => $this->request->get('user'),
                            'url'       => $this->request->get('diff_link'),
                            'wiki_page' => $wiki_page,
                            'old_value' => $this->request->get('version'),
                            'new_value' => $this->request->get('version') + 1)
            );
        }
        $event_manager->processEvent('send_notifications', array());
    }

    function process() {
        if($this->request->get('action')) {
            $this->actionsManagement();
        }
        return $this->viewsManagement();
    }

    function _getDocmanReferences($wiki_page, $group_id) {
        $items = array();
        $item_dao =& $this->_getItemDao();
        if($item_dao->isWikiPageReferenced($wiki_page, $group_id)) {
            $items_ids = $item_dao->getItemIdByWikiPageAndGroupId($wiki_page, $group_id);
            $item_factory =& $this->_getItemFactory();
            if(is_array($items_ids)){
                foreach($items_ids as $key => $id) {
                    $items[] =& $item_factory->getItemFromDb($id);
                }
            }
            else {
                $items[] =& $item_factory->getItemFromDb($items_ids);
            }
        }
        return $items;
    }

    function propagatePermsToNewWikiPage() {
        $wiki_page = $this->request->get('wiki_page');
        $group_id = $this->request->get('group_id');
        $item_dao =& $this->_getItemDao();
        if($item_dao->isWikiPageReferenced($wiki_page, $group_id)) {
            $docman_item_id = $item_dao->getItemIdByWikiPageAndGroupId($wiki_page, $group_id);
            if(isset($docman_item_id) && $docman_item_id) {
                require_once('Docman_PermissionsManager.class.php');
                $dPM =& Docman_PermissionsManager::instance($group_id);
                $dPM->propagatePermsForNewWikiPages($wiki_page, $group_id, $docman_item_id);
            }
        }
    }

    function wiki_before_content() {
        $wiki_page = $this->request->get('wiki_page');
        $group_id = $this->request->get('group_id');
        $item_dao =& $this->_getItemDao();

        $docman_references = HTML();
        // Add js part for toogling referencers section.
        $js_code = '
            function toggle_documents(id) {
                Element.toggle(id);
                toggle_image(id);
            }
            function toggle_image(id) {
                var img_element = $(\'img_\' + id);
                if (img_element.src.indexOf(\'' . util_get_image_theme("ic/toggle_plus.png") . '\') != -1) {
                    img_element.src = \'' . util_get_image_theme("ic/toggle_minus.png") . '\';
                    img_element.title = \'' . $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_hide_referencers') . '\';
                } else {
                    img_element.src = \'' . util_get_image_theme("ic/toggle_plus.png") . '\';
                    img_element.title = \'' . $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_open_referencers') . '\';
                }
            }
                ';
        $docman_references->pushContent(HTML::script(array('type' => 'text/javascript'), $js_code));

        if($item_dao->isWikiPageReferenced($wiki_page, $group_id)){
            $docman_item_id = $item_dao->getItemIdByWikiPageAndGroupId($wiki_page, $group_id);
            if($this->referrerIsDocument()) {
                $referrer_id = $this->getReferrerId($this->getReferrer());
            }
            if(isset($docman_item_id) && $docman_item_id) {
                $GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
                $content = HTML();
                $script = HTML::script(array('type' => 'text/javascript'), "toggle_documents('documents');");
                $user =& $this->getUser();
                $dpm =& Docman_PermissionsManager::instance($group_id);
                // Wiki page could have many references in docman.
                if(is_array($docman_item_id)) {
                    foreach($docman_item_id as $idx => $id) {
                        $can_read = $dpm->userCanAccess($user, $id);
                        if(!$can_read){
                            unset($docman_item_id[$idx]);
                        } 
                    }
                    $icon = HTML::img(array('id' => 'img_documents', 'src' => util_get_image_theme("ic/toggle_minus.png"), 'title' => $GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_open_referencers')));
                    $linked_icon = HTML::a(array('href' => "#", 'onclick' => "javascript:toggle_documents('documents'); return false;"), $icon);
                    
                    // creating the title of the section regarding number of referencing documents and from where we arrived to this wiki page.
                    if (count($docman_item_id) > 1) {
                        $title = "";
                        if(isset($referrer_id) && $referrer_id) {
                            $title = HTML::strong($GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location'));
                        }
                        else {
                            $title = HTML::strong($GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_breadcrumbs_locations'));
                        }
                    }
                    else if(count($docman_item_id) == 1) {
                        $title = HTML::strong($GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location'));
                    }
                    else {
                        $title = "";
                    }
                    
                    //create Full legend of the section
                    $legend = HTML::legend(array('class' => 'docman_md_frame'), 
                            count($docman_item_id) > 1 ? $linked_icon : "", 
                            $title, 
                            isset($referrer_id) && $referrer_id ? HTML($this->showReferrerPath($referrer_id, $group_id)) : "");
                    $details = HTML();

                    // create section body.
                    if(isset($referrer_id) && $referrer_id) {
                        if(count($docman_item_id) > 2){
                            $details->pushContent(HTML::H3($GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_other_locations')));
                        }
                        else if(count($docman_item_id) == 2) {
                            $details->pushContent(HTML::H3($GLOBALS['Language']->getText('plugin_docman', 'docman_wiki_other_location')));
                        }
                    }
                    // create Referencing documents linked paths.
                    foreach($docman_item_id as $index => $value) {
                        $details->pushContent($this->getDocumentPath($value, $group_id, isset($referrer_id) && $referrer_id ? $referrer_id : null));                     
                    }
                    $content->pushContent(HTML::div(array('id' => 'documents'), $details));

                    if(count($docman_item_id) == 1) {
                        $docman_references->pushContent(HTML::strong($GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location')));
                        $docman_references->pushContent(HTML($this->getDocumentPath($docman_item_id[0], $group_id)));
                        $docman_references->pushContent(HTML::br());
                    }
                    else {
                        $docman_references->pushContent(HTML::br());
                        $docman_references->pushContent(HTML::fieldset(array('class' => 'docman_md_frame'), $legend, $content, $script));
                    }
                }
                else { 
                    if($dpm->userCanAccess($user, $docman_item_id)) {
                        $docman_references->pushContent(HTML::strong($GLOBALS['Language']->getText('plugin_docman', 'breadcrumbs_location')));
                        $docman_references->pushContent(HTML($this->getDocumentPath($docman_item_id, $group_id)));
                        $docman_references->pushContent(HTML::br());
                    }
                }
            }
        }

        // Write documents paths on wiki view.
        $this->request->params['html'] = $docman_references;
    }

    function referrerIsDocument() {
        $ref = $this->getReferrer();
        if(isset($ref) && $ref) {
            if(preg_match("/\/plugins\/docman\//", $ref)) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    function getReferrer() {
        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            return $_SERVER['HTTP_REFERER'];
        }
        else {
            return null;
        }
    }

    function getReferrerId($ref) {
        //Refferers are urls like this :  "plugins/docman/index.php?group_id=101&id=37&action=details"
        if(preg_match("/\&action=details\&id\=([0-9]+)/", $ref, $match)) {
            return $match[1];
        }
        if(preg_match("/\&id=([0-9])\&action=details/", $ref, $match)) {
            return $match[1];
        }
        else {
            return null;
        }
    }

    function showReferrerPath($referrer_id, $group_id) {
        $parents = array();
        $html = HTML();
        $hp =& CodeX_HTMLPurifier::instance();
        $item_factory =& $this->_getItemFactory($group_id);
        $item =& $item_factory->getItemFromDb($referrer_id);
        $reference =& $item;
    
        while ($item->getParentId() != 0) {
            $item =& $item_factory->getItemFromDb($item->getParentId());
            $parents[] = array(
                'id'    => $item->getId(),
                'title' => $item->getTitle()
            );
        }

        $parents = array_reverse($parents);
        $item_url = '/plugins/docman/?group_id=' . $group_id . '&sort_update_date=0&action=show&id=';

        foreach($parents as $parent) {
            $html->pushContent(HTML::a(array('href' => $item_url . $parent['id'], 'target' => '_blank'), HTML::strong($parent['title'])));
            $html->pushContent(' / ');
        }

        $md_uri = '/plugins/docman/?group_id=' . $group_id . '&action=details&id=' . $referrer_id;

        $pen_icon = HTML::a(array('href' => $md_uri) ,HTML::img(array('src' => util_get_image_theme("ic/edit.png"))));

        $html->pushContent(HTML::a(array('href' => $item_url . $reference->getId()), HTML::strong($reference->getTitle())));
        $html->pushContent($pen_icon);

        return $html;
    }

    function getDocumentPath($id, $group_id, $referrer_id = null) {
        $parents = array();
        $html = HTML();
        $hp =& CodeX_HTMLPurifier::instance();
        $item_factory =& $this->_getItemFactory($group_id);
        $item =& $item_factory->getItemFromDb($id);
        $reference =& $item;
        if ($reference && $referrer_id != $id) {
            while ($item && $item->getParentId() != 0) {
                $item =& $item_factory->getItemFromDb($item->getParentId());
                $parents[] = array(
                    'id'    => $item->getId(),
                    'title' => $item->getTitle()
                );
            }
            $parents = array_reverse($parents);
            $item_url = '/plugins/docman/?group_id=' . $group_id . '&sort_update_date=0&action=show&id=';
            foreach($parents as $parent) {
                $html->pushContent(HTML::a(array('href' => $item_url . $parent['id'], 'target' => '_blank'), HTML::strong($parent['title'])));
                $html->pushContent(' / ');
            }

            $md_uri = '/plugins/docman/?group_id=' . $group_id . '&action=details&id=' . $id;

            //Add a pen icon linked to document properties.
            $pen_icon = HTML::a(array('href' => $md_uri) ,HTML::img(array('src' => util_get_image_theme("ic/edit.png"))));

            $html->pushContent(HTML::a(array('href' => $item_url . $reference->getId()), HTML::strong($reference->getTitle())));
            $html->pushContent($pen_icon);
            $html->pushContent(HTML::br());
        }
        return $html;
    }

    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }

    var $dao;
    function &_getItemDao() {
        if (!$this->dao) {
            $this->dao =& new Docman_ItemDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }
}
?>