<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap;

use DataAccessResult;

final class FakeDataAccessResult extends DataAccessResult
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->_current = -1;
        $this->_row = false;
        $this->rewind(); // in case getRow is called explicitly
    }

    protected function daFetch()
    {
        return isset($this->data[$this->_current]) ? $this->data[$this->_current] : false;
    }

    protected function daSeek()
    {
        $this->_current = -1;
    }

    protected function daIsError()
    {
        return false;
    }

    public function rowCount()
    {
        return count($this->data);
    }

    public function freeMemory()
    {
    }
}
