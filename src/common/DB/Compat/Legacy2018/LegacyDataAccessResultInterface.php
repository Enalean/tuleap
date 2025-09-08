<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DB\Compat\Legacy2018;

/**
 * @deprecated See \Tuleap\DB\DataAccessObject
 */
interface LegacyDataAccessResultInterface extends \IProvideDataAccessResult
{
    /**
     * @deprecated
     */
    public function getResult();

    /**
     * Allow to create an object instead of an array when iterating over results
     *
     * @param callback $instance_callback The callback to use to create object
     *
     * @deprecated
     *
     * @return LegacyDataAccessResultInterface
     */
    public function instanciateWith($instance_callback);

    /**
     * Returns an array from query row or false if no more rows
     *
     * @deprecated
     *
     * @return mixed
     */
    #[\Override]
    public function getRow();

    /**
     * Returns the number of rows affected
     *
     * @deprecated
     *
     * @return int
     */
    #[\Override]
    public function rowCount();

    /**
     * Returns false if no errors or returns a MySQL error message
     *
     * @deprecated
     *
     * @return mixed
     */
    #[\Override]
    public function isError();

    /**
     * @deprecated
     * @return false|array Return the current element
     * @psalm-ignore-falsable-return
     */
    #[\Override]
    public function current(): mixed;

    /**
     * Move forward to next element.
     *
     * @deprecated
     */
    #[\Override]
    public function next(): void;

    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * @deprecated
     */
    #[\Override]
    public function valid(): bool;

    /**
     * Rewind the Iterator to the first element.
     *
     * @deprecated
     */
    #[\Override]
    public function rewind(): void;

    /**
     * Return the key of the current element.
     *
     * @deprecated
     */
    #[\Override]
    public function key(): mixed;

    /**
     * @deprecated
     */
    #[\Override]
    public function count(): int;

    /**
     * @deprecated
     */
    public function freeMemory();
}
