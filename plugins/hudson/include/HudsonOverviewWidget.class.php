<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * abstract class HudsonOverviewWidget 
 */

require_once('HudsonWidget.class.php');

abstract class HudsonOverviewWidget extends HudsonWidget {
    
    function isUnique() {
        return true;
    }
    
}

?>