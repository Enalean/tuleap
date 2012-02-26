<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_ItemDao.class.php');
require_once('Docman_ItemFactory.class.php');
require_once('Docman_PermissionsManager.class.php');

/**
 * Embed an item in the dashboard
 * - Display the content of embedded documents
 * - Display the image of "image" file documents
 * - else display a link to the item
 * 
 * The display of a folder (its children) would be great
 */
class Docman_Widget_Embedded extends Widget /* implements Visitor */ {
    
    /**
     * The title given by the user to the widget
     */
    protected $plugin_docman_widget_embedded_title;
    
    /**
     * The item id to display
     */
    protected $plugin_docman_widget_embedded_item_id;
    
    /**
     * The path to this plugin
     */
    protected $plugin_path;
    
    /**
     * Constructor
     * @param string $id the internal identifier of the widget (plugin_docman_my_embedded | plugin_docman_project_embedded)
     * @param int $owner_id the id of the owner (user id, group id, ...)
     * @param string $owner_type the type of the owner WidgetLayoutManager::OWNER_TYPE_USER, WidgetLayoutManager::OWNER_TYPE_GROUP, ...
     * @param string $plugin_path the path of the plugin to build urls
     */
    public function __construct($id, $owner_id, $owner_type, $plugin_path) {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
        $this->plugin_path = $plugin_path;
    }
    
    /**
     * Get the title of the widget. Default is 'Embedded Document'
     * Else it is the title given by the user
     * @return string
     */
    public function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->plugin_docman_widget_embedded_title ?  
               $hp->purify($this->plugin_docman_widget_embedded_title, CODENDI_PURIFIER_CONVERT_HTML)  : 
               $GLOBALS['Language']->getText('plugin_docman', 'widget_title_embedded');
    }
    
    /**
     * Compute the content of the widget
     * @return string html
     */
    public function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $content = '';
        if ($this->plugin_docman_widget_embedded_item_id) {
            if ($item = $this->getItem($this->plugin_docman_widget_embedded_item_id)) {
                $content .= $item->accept($this);
                $content .= '<div style="text-align:center"><a href="'. $this->plugin_path .'/?group_id='. (int)$item->getGroupId() .'&amp;action=details&amp;id='.  (int)$item->getId() .'">[Go to document]</div>';
            } else {
                $content .= 'Document doesn\'t exist or you don\'t have permissions to see it';
            }
        }
        return $content;
    }
    
    /**
     * Says if the content of the widget can be displayed through an ajax call
     * If true, then the dashboard will be rendered faster but the page will be a little bit crappy until full load.
     * @return boolean
     */
    public function isAjax() {
        return true;
    }
    
    /**
     * Says if the widget has preferences
     */
    public function hasPreferences() {
        return true;
    }
    
    /**
     * Compute the preferences form for the widget. This form will be displayed 
     * between the content and the title.
     * @return string html
     */
    public function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="plugin_docman_widget_embedded[title]" value="'. $hp->purify($this->plugin_docman_widget_embedded_title, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Item_id:</td><td><input type="text" class="textfield_small" name="plugin_docman_widget_embedded[item_id]" value="'. $hp->purify($this->plugin_docman_widget_embedded_item_id, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    
    /**
     * Compute the preferences form for the widget to display before install.
     * @return string html
     */
    public function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Item id:</td><td><input type="text" class="textfield_small" name="plugin_docman_widget_embedded[item_id]" value="" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    
    /**
     * Clone the content of the widget (for templates)
     * @return int the id of the new content
     * @todo Use dao instead of legacy db functions
     */
    public function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO plugin_docman_widget_embedded (owner_id, owner_type, title, item_id) 
                SELECT  ". $owner_id .", '". $owner_type ."', title, item_id
                FROM plugin_docman_widget_embedded
                WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    
    /**
     * Lazy load the content
     * @param int $id the id of the content
     */
    public function loadContent($id) {
        $sql = "SELECT * FROM plugin_docman_widget_embedded WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->plugin_docman_widget_embedded_title   = $data['title'];
            $this->plugin_docman_widget_embedded_item_id = $data['item_id'];
            $this->content_id = $id;
        }
    }
    
    /**
     * Create a new content for this widget
     * @param Codendi_Request $request
     * @return int the id of the new content
     */
    public function create($request) {
        $content_id = false;
        $vItem_id = new Valid_String('item_id');
        $vItem_id->setErrorMessage("Unable to add the widget. Please give an item id.");
        $vItem_id->required();
        if ($request->validInArray('plugin_docman_widget_embedded', $vItem_id)) {
            $plugin_docman_widget_embedded = $request->get('plugin_docman_widget_embedded');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('plugin_docman_widget_embedded', $vTitle)) {
                if ($item = $this->getItem($plugin_docman_widget_embedded['item_id'])) {
                    $plugin_docman_widget_embedded['title'] = $item->getTitle();
                }
            }
            $sql = 'INSERT INTO plugin_docman_widget_embedded (owner_id, owner_type, title, item_id) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($plugin_docman_widget_embedded['title']) ."', '". db_escape_string($plugin_docman_widget_embedded['item_id']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    
    /**
     * Update the preferences
     * @param Codendi_Request $request
     * @return boolean true if something has been updated
     */
    function updatePreferences($request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($plugin_docman_widget_embedded = $request->get('plugin_docman_widget_embedded')) && $request->valid($vContentId)) {
            $vItem_id = new Valid_String('item_id');
            if($request->validInArray('plugin_docman_widget_embedded', $vItem_id)) {
                $item_id = " item_id   = ". db_ei($plugin_docman_widget_embedded['item_id']) ." ";
            } else {
                $item_id = ' item_id = item_id ';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('plugin_docman_widget_embedded', $vTitle)) {
                $title = " title = '". db_escape_string($plugin_docman_widget_embedded['title']) ."' ";
            } else {
                $title = ' title = title ';
            }

            $sql = "UPDATE plugin_docman_widget_embedded 
                    SET ". $title .", ". $item_id ." 
                    WHERE owner_id   = ". $this->owner_id ." 
                      AND owner_type = '". $this->owner_type ."' 
                      AND id         = ". (int)$request->get('content_id');
            $res = db_query($sql);
            $done = true;
        }
        return $done;
    }
    
    /**
     * The widget has just been removed from the dashboard. 
     * We must delete its content.
     * @param int $id the id of the content
     */
    public function destroy($id) {
        $sql = 'DELETE FROM plugin_docman_widget_embedded WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    
    /**
     * Says if the widget allows (or not) more than one instance on the same dashboard
     * It's up to the widget to decide if it is relevant.
     * @return boolean
     */
    function isUnique() {
        return false;
    }
    
    /**
     * The category of the widget. Override this method if your widget is not in the "general" category.
     * Here are some exemple of categories used by Codendi: forum, frs, scm, trackers + plugin's ones
     * @return string
     */
    function getCategory() {
        return 'plugin_docman';
    }
    
    /**
     * Return an item (we don't know the group_id)
     * @param int $item_id the id of the item to retrieve
     * @return Docman_Item
     */
    protected function getItem($item_id) {
        $item = null;
        $dao = new Docman_ItemDao(CodendiDataAccess::instance());
        if ($row = $dao->searchByid($item_id)->getRow()) {
            $item = Docman_ItemFactory::instance($row['group_id'])->getItemFromRow($row);
            $dPm  = Docman_PermissionsManager::instance($row['group_id']);
            $user = UserManager::instance()->getCurrentUser();
            if (!$dPm->userCanRead($user, $item->getId())) {
                $item = false;
            }
        }
        return $item;
    }
    
    
    function visitFolder($item, $params = array()) {
        // do nothing
        return '';
    }

    function visitDocument($item, $params = array()) {
        // do nothing
        return '';
    }

    function visitWiki($item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitLink($item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitFile($item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitEmbeddedFile($item, $params = array()) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $version = $item->getCurrentVersion();
        if (file_exists($version->getPath())) {
            $em = EventManager::instance();
            $em->processEvent('plugin_docman_event_access', array(
                'group_id' => $item->getGroupId(),
                'item'     => $item,
                'version'  => $version->getNumber(),
                'user'     => UserManager::instance()->getCurrentUser()
            ));
            $mime = explode('/', $version->getFiletype());
            if (in_array($mime[1], array('plain', 'css', 'javascript'))) {
                $balise = 'pre';
            } else {
                $balise = 'div';
            }
            $html .= '<'. $balise .' style="clear:both">';
            $html .= $hp->purify(file_get_contents($version->getPath()), CODENDI_PURIFIER_FULL);
            $html .= '</'. $balise .'>';
        } else {
            $html .= '<em>'. $GLOBALS['Language']->getText('plugin_docman', 'error_filenotfound') .'</em>';
        }
        return $html;
    }

    function visitEmpty($item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_docman','widget_description_embedded');
    }

}
?>
