<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\GitPHP\Events;

use Tuleap\Event\Dispatchable;
use Tuleap\Git\GitPHP\Blob;

class DisplayFileContentInGitView implements Dispatchable
{
    const NAME = 'displayFileContentInGitView';

    /**
     * @var Blob
     */
    private $blob;

    private $is_file_in_special_format = false;

    public function __construct(Blob $blob)
    {
        $this->blob = $blob;
    }

    /**
     * @return Blob
     */
    public function getBlob()
    {
        return $this->blob;
    }

    public function setFileIsInSpecialFormat()
    {
        $this->is_file_in_special_format = true;
    }

    /**
     * @return bool
     */
    public function isFileInSpecialFormat()
    {
        return $this->is_file_in_special_format;
    }
}
