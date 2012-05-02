<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * docblock comment
 */
class Valid {
    
    const STATUS = 1;
    
    public $attribute;
    
    protected $protected_attribute;
    
    var $toto;
    
    /**
     * docblock comment
     * 
     * @param string $param1 The comment for $param1
     * @param int    $param2 The comment for $param2
     */
    public function __construct($param1, $param2) {
        //Simple comment
        if ($param1 == 'toto'
            || $param2 !== 'truc'
        ) {
            /*
            Multi line
            comment
            */
            foreach ($param2 as $k => $v) {
                $this->toto = $v;
            }
        }
    }
    
    /**
     * Multiline declaration
     * 
     * @param string $param1 The comment for $param1
     * @param int    $param2 The comment for $param2
     * 
     * @return void
     */
    protected function multiLine($param1, 
                                 $param2) {
        //do nothing
    }
}
?>
