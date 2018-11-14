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

namespace Tuleap\Git\LFS\Batch\Request;

class BatchRequestObject
{
    /**
     * @var string
     */
    private $oid;
    /**
     * @var int
     */
    private $size;

    private function __construct($oid, $size)
    {
        if (! \is_string($oid)) {
            throw new \TypeError('Expected $oid to be a string, got ' . gettype($oid));
        }
        $this->oid = $oid;
        if (! \is_int($size)) {
            throw new \TypeError('Expected $size to be an int, got ' . gettype($size));
        }
        if ($size < 0) {
            throw new \InvalidArgumentException('The size must be positive');
        }
        $this->size = $size;
    }

    /**
     * @throws IncorrectlyFormattedBatchRequestException
     * @return BatchRequestObject
     */
    public static function buildFromObject(\stdClass $parameters)
    {
        if (! isset($parameters->oid, $parameters->size)) {
            throw new IncorrectlyFormattedBatchRequestException('oid and size should be present in a batch request object');
        }
        try {
            return new self($parameters->oid, $parameters->size);
        } catch (\TypeError $error) {
            throw new IncorrectlyFormattedBatchRequestException(
                'Incorrect value for a batch request object. ' . $error->getMessage()
            );
        } catch (\InvalidArgumentException $exception) {
            throw new IncorrectlyFormattedBatchRequestException(
                'Incorrect value for a batch request object. ' . $exception->getMessage()
            );
        }
    }

    /**
     * @return string
     */
    public function getOID()
    {
        return $this->oid;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
}
