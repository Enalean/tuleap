
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');

/**
 * <?=$class_name?>Actions
 */
class <?=$class_name?>Actions extends Actions {
    
    function <?=$class_name?>Actions(&$controler, $view=null) {
        $this->Actions($controler);
	}
	
	// {{{ Actions
    
    // }}}
    
    
}


