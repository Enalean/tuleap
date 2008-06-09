<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
 */

class colorsFactory{
    
    var $colors_rvb;
    var $colors_named;
    var $colors_hexstr;
    
    /**
    * class constructor
    *
    * @return null
    */

    function colorsFactory() {
       $this->colors_named = array(
            'lightsalmon',
            'palegreen',
            'palegoldenrod',
            'lightyellow',
            'paleturquoise',
            'steelblue1',
            'thistle',
            'palevioletred1',
            'olivedrab1',
            'gold',
            'red',
            'gray9',
            'salmon',
            'darkgreen',
            'white',
            'darkblue'
            
       );
    }
    
    /**
    * function to get color name from a number
    *   
    * @return color name
    */
    function getColor_name($num) {
        return $this->colors_named[$num % count($this->colors_named)];
    }
    
    /**
     * return all used colors
     */
    function getColors() {
        return $this->colors_named;
    }

}
?>
