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

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/dao/SupportedLanguagesDao.class.php');
Mock::generate('SupportedLanguagesDao');

require_once('common/language/LanguageManager.class.php');

class LanguageManagerTest extends UnitTestCase {
    
    function LanguageManagerTest($name = 'LanguageManager test') {
        $this->UnitTestCase($name);
    }

    function testGetLanguageCodeFromLanguageId() {
        $dao =& new MockSupportedLanguagesDao($this);
        $dar =& new MockDataAccessResult($this);
        $dao->setReturnReference('searchByLanguageId', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('language_id' => 123, 'language_code' => 'code 123'));
        $dar->setReturnValueAt(0, 'getRow', false);
        
        $lm =& new LanguageManager($dao);
        $this->assertEqual('code 123', $lm->getLanguageCodeFromLanguageId(123));
    }
}
?>
