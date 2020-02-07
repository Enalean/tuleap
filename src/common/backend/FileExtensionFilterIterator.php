<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Backend;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;

class FileExtensionFilterIterator extends RecursiveFilterIterator
{
    /**
     * @var array
     */
    private $allowed_extensions;

    public function __construct(RecursiveDirectoryIterator $iterator, array $allowed_extensions)
    {
        parent::__construct($iterator);
        $this->allowed_extensions = $allowed_extensions;
    }

    public function accept(): bool
    {
        $file = $this->current();
        if ($file->isDir() || empty($this->allowed_extensions)) {
            return true;
        }

        $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

        return in_array($extension, $this->allowed_extensions);
    }

    /**
     * @return FileExtensionFilterIterator
     */
    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->allowed_extensions);
    }
}
