<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/project/ProjectManager.class.php');
require_once('common/user/UserManager.class.php');
require_once('CodendiSolrDocument_DocmanItem.class.php');
require_once('CodendiSolrDocument_DocmanItemViewer.class.php');
require_once('CodendiSolrDocument_ForumMessage.class.php');
require_once('CodendiSolrDocument_ForumMessageViewer.class.php');
require_once(dirname(__FILE__) . '/../etc/solr/SolrPhpClient/Apache/Solr/Document.php' );

class CodendiSolrDocumentFactory {
    
    const DOCUMENT      = 'codendi_document';
    const FORUM_MESSAGE = 'codendi_forum_message';
    const NEWS          = 'codendi_news';
    
    protected static $_instance;
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }
    
    /**
     * Returns the CodendiSolrDocument built from the event params array
     *
     * @param Apache_Solr_Document the document returned by Solr
     *
     * @return ICodendiSolrDocumentViewer the CodendiSolrDocumentViewer or null if $document does not correspond with any CodendiSolrDocument
     */
    public function getCodendiSolrDocumentViewer(Apache_Solr_Document $document) {
        switch ($document->document_type) {
        case self::DOCUMENT:
            return new CodendiSolrDocument_DocmanItemViewer($document);
            break;
        case self::FORUM_MESSAGE:
            return new CodendiSolrDocument_ForumMessageViewer($document);
            break;
        default:
            break;
        }
        return null;
    }
    
    /**
     * Returns the CodendiSolrDocument built from the event params array
     *
     * @param string the event name
     * @param array  the event parameters
     *
     * @return ICodendiSolrDocumentFactory the CodendiSolrDocument or null if $params does not correspond with any CodendiSolrDocument
     */
    public function getCodendiSolrDocumentFromEventParams($event, $params) {
        $codendi_solr_document = null;
        switch ($event) {
        case 'plugin_docman_event_add':
            $item      = $params['item'];
            $files     = $params['file'];
            if ($files) {
                $nb_versions = count($files);
                $latest_version = $files[$nb_versions - 1];
                $path = $latest_version->getPath();
            } else {
                $path = '';
            }
            $metadatas = $params['metadata'];
            $codendi_solr_document  = $this->_buildSolrDocumentFromCodendiItem($item, $path, $metadatas);
            break;
        case 'plugin_docman_event_update':
            $new_item  = $params['data'];
            $files     = $params['file'];
            if ($files) {
                $nb_versions = count($files);
                $latest_version = $files[0];
                $path = $latest_version->getPath();
            } else {
                $path = '';
            }
            $metadatas = $params['metadata'];
            $codendi_solr_document  = $this->_buildSolrDocumentFromCodendiItem($new_item, $path, $metadatas);
            break;
        case'plugin_docman_event_new_version':
            $item  = $params['item'];
            $path = $params['new_version']['path'];
            $metadatas = $params['metadata'];
            $codendi_solr_document  = $this->_buildSolrDocumentFromCodendiItem($item, $path, $metadatas);
            break;
        case 'forum_event_add_message':
            $user_id = $params['user_id'];
            $group_id = $params['group_id'];
            $forum_id = $params['forum_id'];
            $forum_message_id = $params['forum_message_id'];
            $forum_message_url = $params['forum_message_url'];
            $forum_message_subject = $params['forum_message_subject'];
            $forum_message_body = $params['forum_message_body'];
            $forum_message_date = $params['forum_message_date'];
            $codendi_solr_document = $this->_buildSolrDocumentFromCodendiForumMessage($user_id, $group_id, $forum_id, $forum_message_id, $forum_message_url, $forum_message_subject, $forum_message_body, $forum_message_date);
            break;
        default:
            $codendi_solr_document = null;
            break;
        }
        return $codendi_solr_document;
    }
    
    /**
     * Build a SolR document from a Codendi document (Docman_Item)
     *
     * @param Docman_Item $item      the codendi document
     * @param string      $path      the path of the document
     * @param array       $metadatas array of document metadata (array of key => metadata)
     *
     * @return CodendiSolrDocument_DocmanItem the solR document with values from Codendi document
     */
    private function _buildSolrDocumentFromCodendiItem(Docman_Item $item, $path, $metadatas) {
        $document = new CodendiSolrDocument_DocmanItem();
        // Id
        $document->id = self::DOCUMENT . '_' . $item->getId();
        $document->doc_item_id = $item->getId();
        
        // Document type
        $document->document_type = self::DOCUMENT;
        
        $group_id = $item->getGroupId();
        $project  = ProjectManager::instance()->getProject($group_id);
        $owner    = UserManager::instance()->getUserById($item->getOwnerId());

        // Group (project) Id
        $document->forge_project_id = $group_id;
        // Group (project) name
        $document->forge_project_name = $project->getPublicName();
        // Document URL
        $document->document_url = $item->getDetailsURL();
        // comments: usefull? mandatory?
        $document->comments= '';
        // Path
        if ($path) {
            $document->file_address = $path;
        }
        // Title
        $document->doc_title = $item->getTitle();
        // Description
        $document->doc_description = $item->getDescription();
        // Owner Id
        $document->doc_owner = $item->getOwnerId();
        // Owner name
        $document->doc_owner_name = $owner->getUserName();
        // Create date 
        $document->doc_create_date  = $this->_timestampToSolrDate($item->getCreateDate());
        $document->doc_create_year  = date("Y", $item->getCreateDate());
        $document->doc_create_month = date("m", $item->getCreateDate());
        $document->doc_create_day   = date("d", $item->getCreateDate());
        // Update date (format? ISO 8601 ?)
        $document->doc_update_date  = $this->_timestampToSolrDate($item->getUpdateDate());
        $document->doc_update_year  = date("Y", $item->getUpdateDate());
        $document->doc_update_month = date("m", $item->getUpdateDate());
        $document->doc_update_day   = date("d", $item->getUpdateDate());
        // Language: We don't know the document's language. Integrated language guesser?
        //$document->doc_language = 'english';
        // Metadata
        $metas_array = array();
        if ($metadatas) {
            foreach ($metadatas as $meta_key => $meta_value) {
                if (is_array($meta_value)) {
                    // possible if metadata type is a multi valued list
                    foreach ($meta_value as $meta_list_value) {
                        $metas_array[] = $meta_list_value;
                    }
                } else {
                    $metas_array[] = $meta_value;
                }
            }
            $document->doc_extra_field = $metas_array;
        }
        // Permissions
        $dpm = Docman_PermissionsManager::instance($group_id);
        $perms = $dpm->getUgroupsIdWithReadPermissions($item);
        $document->forge_permission_group = $perms;
        
        return $document;
    }
    
    /**
     * Build a SolR document from a Codendi forum messages params
     *
     * @param int    $user_id                the user that posted the message
     * @param int    $group_id               the project the message belongs to
     * @param int    $forum_id               the forum the message belongs to
     * @param int    $forum_message_id       the ID of the message
     * @param int    $forum_message_url      the URL of the message
     * @param string $forum_message_subject  the subject of the message
     * @param string $forum_message_body     the body of the message
     * @param int    $forum_message_date     the timestamp the message was posted
     *
     * @return CodendiSolrDocument_ForumMessage the solR document with values from Codendi forum message
     */
    private function _buildSolrDocumentFromCodendiForumMessage($user_id, $group_id, $forum_id, $forum_message_id, $forum_message_url, $forum_message_subject, $forum_message_body, $forum_message_date) {
        $document = new CodendiSolrDocument_ForumMessage();
        // Id
        $document->id = self::FORUM_MESSAGE . '_' . $forum_message_id;
        
        // Document type
        $document->document_type = self::FORUM_MESSAGE;
        
        $project  = ProjectManager::instance()->getProject($group_id);
        $owner    = UserManager::instance()->getUserById($user_id);
        
        // Group (project) Id
        $document->forge_project_id = $group_id;
        // Group (project) name
        $document->forge_project_name = $project->getPublicName();
        // Document URL
        $document->document_url = $forum_message_url;
        // Owner Id
        $document->forum_message_author_id = $user_id;
        // Owner name
        $document->forum_message_author_name = $owner->getUserName();
        // Message Id
        $document->forum_message_id = $forum_message_id;
        // Message subject
        $document->forum_message_subject = $forum_message_subject;
        // Message body
        $document->forum_message_body = $forum_message_body;
        // Create date 
        $document->forum_message_date  = $this->_timestampToSolrDate($forum_message_date);
        $document->forum_message_year  = date("Y", $forum_message_date);
        $document->forum_message_month = date("m", $forum_message_date);
        $document->forum_message_day   = date("d", $forum_message_date);
        return $document;
    }
    
    /**
     * Return a SolR date from a unix timestamp (format? ISO 8601 ?)
     *
     * @param int $timestamp the unix timestamp to convert
     *
     * @return string the solR date time
     */
    private function _timestampToSolrDate($timestamp) {
        return date("Y-m-d", $timestamp) . "T" . date("H:i:s", $timestamp) . "Z";
    }
}

?>
