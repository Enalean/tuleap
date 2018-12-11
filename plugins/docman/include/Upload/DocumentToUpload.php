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

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusFile;

final class DocumentToUpload implements TusFile
{
    /**
     * @var resource
     */
    private $handle;
    /**
     * @var int
     */
    private $length;
    /**
     * @var int
     */
    private $offset;

    public function __construct($handle, $length, $offset)
    {
        if (! \is_resource($handle)) {
            throw new \InvalidArgumentException(
                'Expected a resource to the document, got ' . gettype($handle)
            );
        }
        $this->handle = $handle;
        if ($length < 0) {
            throw new \UnexpectedValueException('The length must be positive');
        }
        $this->length = $length;
        if ($offset < 0) {
            throw new \UnexpectedValueException('The offset must be positive');
        }
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return \resource
     */
    public function getStream()
    {
        return $this->handle;
    }
}
