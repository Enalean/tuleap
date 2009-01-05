<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * abstract class hudson_Widget 
 */

require_once('common/widget/Widget.class.php');

abstract class HudsonWidget extends Widget {
    
    function getCategory() {
        return 'ci';
    }
    
}

?>
