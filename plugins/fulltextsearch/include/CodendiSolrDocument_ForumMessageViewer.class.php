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

require_once('ICodendiSolrDocumentViewer.class.php');
require_once(dirname(__FILE__) . '/../etc/solr/SolrPhpClient/Apache/Solr/Document.php' );

class CodendiSolrDocument_ForumMessageViewer implements ICodendiSolrDocumentViewer {
    
    private $solr_doc;
    
    public function __construct($document) {
        $this->solr_doc = $document;
    }
    
    /**
     * Returns the HTML part to display in the result list for this SolrDocument
     *
     * @return string the HTML part to display in the result list for this SolrDocument
     */
     public function getHTMLResult() {
        $user = UserManager::instance()->getCurrentUser();
        $user_helper = UserHelper::instance();
        
        $html = '';
        $user = UserManager::instance()->getCurrentUser();
        $user_helper = UserHelper::instance();
        $html .= '<li class="fulltextsearch_result">';
                        
        // Quick and dirty
        if ($this->solr_doc->document_url) {
            $doc_url = $solr_doc->document_url;   
        } else {
            $doc_url = '/plugins/docman/?group_id='.$this->solr_doc->forge_project_id.'&action=details&id='.$this->solr_doc->id;
        }
        $doc_date = $this->solr_doc->forum_message_date;
        $doc_date = str_replace("T", " ", $doc_date);
        $doc_date = str_replace("Z", " ", $doc_date);
        $owner_name = $user_helper->getLinkOnUserFromUserId($this->solr_doc->forum_message_author_id);

        $html .= '<p class="fulltextsearch_result_title">';
        $html .= '<a href="'.$doc_url.'" title="forum #'.$this->solr_doc->forum_message_id.'">'.$this->solr_doc->forum_message_subject.'</a>';
        $html .= '</p>';
        $html .= '<p class="fulltextsearch_result_description">';
        $html .= $this->solr_doc->forum_message_body;
        $html .= '</p>';
        $html .= '<p class="fulltextsearch_result_by_on">';
        $html .= '<span class="fulltextsearch_result_owner">by '.$owner_name.'</span> <span class="fulltextsearch_result_date">on '.$doc_date.'</span>';
        $html .= '</p>';
        $html .= '</li>'; '<li class="fulltextsearch_result">';
        return $html;
     }
    
}

?>
