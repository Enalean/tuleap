<?php
/* 
 * Originally written by Marc Nazarian, 2009
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
 *
 */
require_once('Docman_View_ItemDetailsSection.class.php');
require_once('common/reference/CrossReferenceFactory.class.php');
require_once('common/reference/ReferenceManager.class.php');

class Docman_View_ItemDetailsSectionReferences extends Docman_View_ItemDetailsSection {
    
    
    function __construct(&$item, $url) {
        parent::__construct($item, $url, 'references', $GLOBALS['Language']->getText('plugin_docman','details_references'));
    }
    
    function getContent() {
        $crf = new CrossReferenceFactory($this->item->getId(), ReferenceManager::REFERENCE_NATURE_DOCUMENT, $this->item->getGroupId());
        $crf->fetchDatas();
        $content = $crf->getHTMLDisplayCrossRefs();
        return $content;
    }
}
?>
