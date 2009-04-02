<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

require_once('HTML_Element_Selectbox.class.php');

/**
 * Define an html selectbox field for text fields provided by the tracker
 */
class HTML_Element_Selectbox_TrackerFields_Texts extends HTML_Element_Selectbox {

    public function __construct($label, $name, $value, $with_none = false, $onchange = "", $desc="") {
        parent::__construct($label, $name, $value, $with_none, $onchange, $desc);
        
        require_once('common/tracker/ArtifactFieldFactory.class.php');
        require_once('common/tracker/ArtifactType.class.php');
        $at  = new ArtifactType($GLOBALS['ath']->Group,$GLOBALS['ath']->getID(),false);
        $aff = new ArtifactFieldFactory($at);
        foreach ( $aff->getAllUsedFields() as $field) {    	
        	if($field->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
	            if ($field->isTextField()) {
	                $selected = $this->value == $field->getName();
	                $this->addOption(new HTML_Element_Option($field->getLabel(), $field->getName(), $selected));
	            }
        	}
        }
    }
}
?>
