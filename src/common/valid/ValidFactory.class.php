<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package CodeX
 */

require_once('common/valid/Valid.class.php');

/**
 * Check that value is a decimal integer greater or equal to zero.
 */
class Valid_UInt
extends Valid {
    function validate($value) {
        $this->addRule(new Rule_Int());
        $this->addRule(new Rule_GreaterOrEqual(0));
        return parent::validate($value);
    }

}

/**
 * Check that group_id variable is valid
 */
class Valid_GroupId
extends Valid {
    function Valid_GroupId() {
        parent::Valid('group_id');
        //$this->setErrorMessage($GLOBALS['Language']->getText('include_exit','no_gid_err'));
    }

    function validate($value) {
        $this->addRule(new Rule_Int());
        $this->addRule(new Rule_GreaterThan(0));
        return parent::validate($value);
    }
}

/**
 * Check that 'pv' parameter is set to an acceptable value.
 */
class Valid_Pv
extends Valid {
    function Valid_Pv() {
        parent::Valid('pv');
    }

    function validate($value) {
        $this->addRule(new Rule_WhiteList(array(0,1,2)));
        return parent::validate($value);
    }
}

/**
 * Check that value is a string (should always be true).
 */
class Valid_Text
extends Valid {
    function validate($value) {
        $this->addRule(new Rule_String());
        return parent::validate($value);
    }
}

/**
 * Check that value is a string with neither carrige return nor null char.
 */
class Valid_String
extends Valid_Text {
    function validate($value) {
        $this->addRule(new Rule_NoCr());
        return parent::validate($value);
    }
}

/**
 * Wrapprt for 'WhiteList' rule
 */
class Valid_WhiteList
extends Valid {
    function Valid_WhiteList($key, $whitelist) {
        parent::Valid($key);
        $this->addRule(new Rule_WhiteList($whitelist));
    }
}

/**
 * @todo Valid_Username
 */

/**
 * @todo Valid_Email
 */
class Valid_Email
extends Valid_String {
}

?>
