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

use Tuleap\Git\HTTP\GitHTTPOperation;

class BatchRequest implements GitHTTPOperation
{
    /**
     * @var BatchRequestOperation
     */
    private $operation;
    /**
     * @var BatchRequestObject[]
     */
    private $objects;
    /**
     * @var BatchRequestTransfer[]
     */
    private $transfers;
    /**
     * @var BatchRequestReference|null
     */
    private $reference;

    private function __construct(
        BatchRequestOperation $operation,
        array $objects,
        array $transfers,
        BatchRequestReference $reference = null
    ) {

        $this->operation = $operation;
        $this->objects   = $objects;
        $this->transfers = $transfers;
        if (empty($this->transfers)) {
            $this->transfers = [BatchRequestTransfer::buildBasicTransfer()];
        }
        $this->reference = $reference;
    }

    /**
     * @throws IncorrectlyFormattedBatchRequestException
     * @return self
     */
    public static function buildFromJSONString($json_string)
    {
        $decoded_json           = json_decode($json_string);
        $json_decode_error_code = json_last_error();
        if ($json_decode_error_code !== JSON_ERROR_NONE) {
            throw new IncorrectlyFormattedBatchRequestException('JSON is not valid: ' . json_last_error_msg());
        }
        return self::buildFromObject($decoded_json);
    }

    /**
     * @throws IncorrectlyFormattedBatchRequestException
     * @return self
     */
    private static function buildFromObject(\stdClass $parameters)
    {
        if (! isset($parameters->operation, $parameters->objects)) {
            throw new IncorrectlyFormattedBatchRequestException('operation and objects should be present in the request');
        }

        $operation = new BatchRequestOperation($parameters->operation);

        if (! \is_array($parameters->objects)) {
            throw new IncorrectlyFormattedBatchRequestException('objects value of the batch request is expected to be an array');
        }
        $objects = [];
        foreach ($parameters->objects as $object_value) {
            if (! \is_object($object_value)) {
                throw new IncorrectlyFormattedBatchRequestException('Batch request objects are expected to be an object');
            }
            $objects[] = BatchRequestObject::buildFromObject($object_value);
        }

        $transfers = [];
        if (isset($parameters->transfers)) {
            if (! \is_array($parameters->transfers)) {
                throw new IncorrectlyFormattedBatchRequestException('transfers value of the batch request is expected to be an array');
            }
            foreach ($parameters->transfers as $transfer_identifier) {
                try {
                    $transfers[] = new BatchRequestTransfer($transfer_identifier);
                } catch (\TypeError $error) {
                    throw new IncorrectlyFormattedBatchRequestException(
                        'transfer identifier of a batch request object should be a string'
                    );
                }
            }
        }

        $reference = null;
        if (isset($parameters->ref)) {
            if (! \is_object($parameters->ref) || ! isset($parameters->ref->name)) {
                throw new IncorrectlyFormattedBatchRequestException(
                    'ref value of the batch request is expected to be an object with a name'
                );
            }
            $reference = new BatchRequestReference($parameters->ref->name);
        }

        return new self($operation, $objects, $transfers, $reference);
    }

    /**
     * @return BatchRequestOperation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @return BatchRequestObject[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return BatchRequestTransfer[]
     */
    public function getTransfers()
    {
        return $this->transfers;
    }

    /**
     * @return BatchRequestReference|null
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return bool
     */
    public function isWrite()
    {
        return $this->getOperation()->isUpload();
    }
}
