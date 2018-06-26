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

namespace Tuleap\Tracker\Webhook;

use Tuleap\Webhook\Payload;

class ArtifactPayload implements Payload
{

    /**
     * @return array
     */
    public function getPayload()
    {
        /*
         * This is a fake payload here so that we can check in the destination server
         * that this fake content is sent. This will be replaced by the real payload quickly.
         */
        return ['title' => 'This is a payload form an artifact action'];
    }
}
