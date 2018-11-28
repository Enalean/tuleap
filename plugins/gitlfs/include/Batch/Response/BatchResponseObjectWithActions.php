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

namespace Tuleap\GitLFS\Batch\Response;

use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActions;
use Tuleap\GitLFS\Object\LFSObject;

class BatchResponseObjectWithActions implements BatchResponseObject
{
    /**
     * @var LFSObject
     */
    private $object;
    /**
     * @var BatchResponseActions[]
     */
    private $actions;

    public function __construct(LFSObject $object, BatchResponseActions $actions)
    {
        $this->object  = $object;
        $this->actions = $actions;
    }

    public function jsonSerialize()
    {
        return [
            'oid'           => $this->object->getOID()->getValue(),
            'size'          => $this->object->getSize(),
            'authenticated' => true,
            'actions'       => $this->actions
        ];
    }
}
