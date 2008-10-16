
/*
 * Copyright (c) Xerox, <?=date('Y')?>. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, <?=date('Y')?>. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once('common/plugin/PluginDescriptor.class.php');


/**
 * <?=$class_name?>PluginDescriptor
 */
class <?=$class_name?>PluginDescriptor extends PluginDescriptor {
    
    function <?=$class_name?>PluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_name'), '<?=$version?>', $GLOBALS['Language']->getText('plugin_<?=$short_name?>', 'descriptor_description'));
    }
}
