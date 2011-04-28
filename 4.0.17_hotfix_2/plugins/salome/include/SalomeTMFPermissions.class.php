<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFPermission
 */

class SalomeTMFPermissions {

    /**
     * @var int $binary_permissions the binary value of the permissions
     */
    var $_binary_permissions;
    
    /**
     * Build a permission regarding the int value of the permission
     *
     * @param int $permissions the int value of the permission
     */
    function SalomeTMFPermissions($permissions) {
        $this->_binary_permissions = str_pad(decbin($permissions), 8, "0", STR_PAD_LEFT);
    }
    
    /* static */ function getPermissionFromCheckbox($suite_add, $suite_modify, $suite_delete,
                                                    $campaign_add, $campaign_modify, $campaign_delete, $campaign_execute) {
        $int_perm = 0;
        if ($suite_add == 'on') {
            $int_perm += 2;
        }
        if ($suite_modify == 'on') {
            $int_perm += 4;
        }
        if ($suite_delete == 'on') {
            $int_perm += 8;
        }
        
        if ($campaign_add == 'on') {
            $int_perm += 16;
        }
        if ($campaign_modify == 'on') {
            $int_perm += 32;
        }
        if ($campaign_delete == 'on') {
            $int_perm += 64;
        }
        if ($campaign_execute == 'on') {
            $int_perm += 128;
        }
        return new SalomeTMFPermissions($int_perm);
    }
    
    /* private */ function _testWithBinaryMask($binary_mask) {
        return (($this->_binary_permissions & $binary_mask) == $binary_mask);
    }
    
    function getBinaryPermissions() {
        return $this->_binary_permissions;
    }
    
    function getIntPermissions() {
        return bindec($this->_binary_permissions);
    }
    
    function canAddSuite() {
        return $this->_testWithBinaryMask('00000010');
    }
    
    function canModifySuite() {
        return $this->_testWithBinaryMask('00000100');
    }
    
    function canDeleteSuite() {
        return $this->_testWithBinaryMask('00001000');
    }
    
    function canAddCampaign() {
        return $this->_testWithBinaryMask('00010000');
    }
    
    function canModifyCampaign() {
        return $this->_testWithBinaryMask('00100000');
    }
    
    function canDeleteCampaign() {
        return $this->_testWithBinaryMask('01000000');
    }
    
    function canExecuteCampaign() {
        return $this->_testWithBinaryMask('10000000');
    }
    
    /**
     * Test if the values of this permissions are allowed or not
     * 
     * Allowed values for suite: 4 , 6 , 14
     * Allowed values for campaign: 32, 48, 112, 128, 160, 176, 240
     * Then all suite/campaign mix are allowed (e.g. 4+32; 4+112; 6+176; etc.).
     *
     * @return true is the values of this permissions are allowed, false otherwise
     */
    function isAllowedValue() {
        $allowed_values = array(0, 4, 6, 14, 32, 36, 38, 46, 48, 52, 54, 62, 112, 116, 118, 126, 128, 132, 134, 142, 160, 164, 166, 174, 176, 180, 182, 190, 240, 244, 246, 254);
        return in_array($this->getIntPermissions(), $allowed_values);
    }
}

?>
