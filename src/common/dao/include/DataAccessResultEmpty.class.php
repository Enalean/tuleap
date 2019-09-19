<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'IProvideDataAccessResult.class.php';

/**
 * Null object alternative for DataAccessResult
 *
 * Use it when you feel returning something empty without teadious if/else code
 * in calling method
 * @deprecated See \Tuleap\DB\DataAccessObject
 */
class DataAccessResultEmpty implements IProvideDataAccessResult
{

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    public function getRow()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return int
     */
    public function rowCount()
    {
        return 0;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    public function isError()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    public function current()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return void
     */
    public function next()
    {
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    public function valid()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return void
     */
    public function rewind()
    {
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    public function key()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return int
     */
    public function count()
    {
        return 0;
    }

    /**
     * @deprecated
     */
    public function instanciateWith()
    {
        return $this;
    }
}
