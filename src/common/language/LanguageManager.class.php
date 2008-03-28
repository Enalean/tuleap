<?php
/* 
 * Copyright (c) The CodeX Team, Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008
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
 * 
 */

require_once('common/dao/SupportedLanguagesDao.class.php');

class LanguageManager {
    
    var $_languagedao;
    var $_languagecodes;
    
    function LanguageManager(&$languagedao) {
        $this->_languagecodes = array();
        $this->_languagedao =& $languagedao;
    }
    
    function &instance() {
        static $_languagemanager_instance;
        if (!$_languagemanager_instance) {
            $languagedao = new SupportedLanguagesDao(CodeXDataAccess::instance());
            $_languagemanager_instance = new LanguageManager($languagedao);
        }
        return $_languagemanager_instance;
    }

    /**
     * getLanguageCodeFromLanguageId
     * @param int $language_id the id of the language in codex db: 1, 2, ...
     * @return string en_US, fr_FR, ...
     */
    function getLanguageCodeFromLanguageId($language_id) {
        if (!isset($this->_languagecodes[$language_id])) {
            $this->_languagecodes[$language_id] = null;
            $dar =& $this->_languagedao->searchByLanguageId($language_id);
            if ($dar && ($row = $dar->getRow())) {
                $this->_languagecodes[$language_id] = $row['language_code'];
            }
        }
        return $this->_languagecodes[$language_id];
    }
    
}
?>
