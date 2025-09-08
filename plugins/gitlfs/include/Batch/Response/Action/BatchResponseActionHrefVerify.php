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

namespace Tuleap\GitLFS\Batch\Response\Action;

use Tuleap\GitLFS\LFSObject\LFSObject;

final class BatchResponseActionHrefVerify implements BatchResponseActionHref
{
    /**
     * @var string
     */
    private $server_url;
    /**
     * @var LFSObject
     */
    private $object;

    public function __construct($server_url, LFSObject $object)
    {
        $this->server_url = $server_url;
        $this->object     = $object;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getHref()
    {
        return $this->server_url . '/git-lfs/objects/' . urlencode($this->object->getOID()->getValue()) . '/verify';
    }
}
