<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once 'IProvideDataAccessResult.php';

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
    #[\Override]
    public function getRow()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return int
     */
    #[\Override]
    public function rowCount()
    {
        return 0;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     * @return bool
     */
    #[\Override]
    public function isError()
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function current(): bool
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function next(): void
    {
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function valid(): bool
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function rewind(): void
    {
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function key(): bool
    {
        return false;
    }

    /**
     * @see IProvideDataAccessResult
     * @deprecated
     */
    #[\Override]
    public function count(): int
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
