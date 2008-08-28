<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Arnaud Salvucci, 2008
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

/**
 * iStatement
 * this interface is implemented by filters
 * (user filter and group filter)
 * It defines a pattern to build SQL requests
 */
interface IStatement
{
    /**
     * getJoin()
     * The "JOIN" statement
     * Must be implemented
     *
     * @return void
     */
    public function getJoin();

    /**
     * getWhere()
     * The "WHERE" statement
     * Must be implemented
     *
     * @return void
     */
    public function getWhere();

    /**
     * getGroupBy()
     * The "GROUP By" statement
     * Must be implemented
     *
     * @return void
     */
    public function getGroupBy();

}

?>
