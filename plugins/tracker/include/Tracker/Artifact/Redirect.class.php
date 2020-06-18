<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class Tracker_Artifact_Redirect
{
    public const STATE_CREATE_PARENT = 'to_parent';
    public const STATE_STAY          = 'stay';
    public const STATE_CONTINUE      = 'continue';
    public const STATE_SUBMIT        = 'submit';

    /**
     * @var string
     */
    public $mode = '';
    /**
     * @var string
     */
    public $base_url = '';
    /**
     * @var array<string, string>
     */
    public $query_parameters = [];

    public function toUrl(): string
    {
        $base_url = rtrim((string) $this->base_url, '/');

        if (empty($this->query_parameters)) {
            return $base_url;
        }

        return $base_url . '/?' . http_build_query($this->query_parameters);
    }

    public function stayInTracker(): bool
    {
        return $this->mode !== self::STATE_SUBMIT;
    }
}
