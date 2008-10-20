<?php 
 /**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Ikram BOUOUD, 2008
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
require_once('pre.php');
require_once(dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
require_once('common/user/UserManager.class.php');
require_once('showPermsVisitor.class.php'); 
require_once('common/event/EventManager.class.php');

$GLOBALS['Language']->loadLanguageMsg('eac', 'eac');
 
$valueGroupId = new Valid_UInt('group_id');
if($valueGroupId->validate($group_id)) {
    $group_id = $request->get('group_id'); 
    $docmanItem = array();
    $listItem   = array();
    $ugroups    = array();
    
    $um   = UserManager::instance();
    $user = $um->getCurrentUser();
    
    $params['user']            = $user;
    $params['ignore_collapse'] = true;
    $params['ignore_perms']    = true;
    $params['ignore_obsolete'] = false;
    
    $itemFactory = new Docman_ItemFactory($group_id);
    $node        = $itemFactory->getItemTree(0, $params);
    $visitor     = new showPermsVisitor(); 
    $visitor->visitFolder($node, $docmanItem);
    $visitor->csvFormatting($ugroups, $listItem, $group_id);

}else {
    exit_no_group();
}

?> 
