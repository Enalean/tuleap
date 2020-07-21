<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Request;

use Tuleap\GitLFS\HTTP\GitLfsHTTPOperation;
use Tuleap\GitLFS\HTTP\RequestReference;

class LockVerifyRequest implements GitLfsHTTPOperation
{
    /**
     * @var RequestReference|null
     */
    private $reference;

    public function __construct(?RequestReference $reference)
    {
        $this->reference = $reference;
    }

    public static function buildFromJSONString(string $json_string): LockVerifyRequest
    {
        $decoded_json           = json_decode($json_string);
        $json_decode_error_code = json_last_error();

        if ($json_decode_error_code !== JSON_ERROR_NONE) {
            throw new IncorrectlyFormattedReferenceRequestException('JSON is not valid: ' . json_last_error_msg());
        }

        return self::buildFromObject($decoded_json);
    }

    private static function buildFromObject(\stdClass $parameters): LockVerifyRequest
    {
        $reference = null;
        if (isset($parameters->ref)) {
            if (! isset($parameters->ref->name)) {
                throw new IncorrectlyFormattedReferenceRequestException(
                    'ref value of the lock verify request is expected to be an object with a name'
                );
            }
            $reference = new RequestReference($parameters->ref->name);
        }

        return new self($reference);
    }

    public function isWrite(): bool
    {
        return true;
    }

    public function isRead(): bool
    {
        return false;
    }

    public function getReference(): ?RequestReference
    {
        return $this->reference;
    }
}
