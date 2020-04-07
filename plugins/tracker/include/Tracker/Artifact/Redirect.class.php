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
    public const STATE_CREATE_PARENT    = 'to_parent';
    public const STATE_STAY_OR_CONTINUE = 'stay_continue';
    public const STATE_SUBMIT           = 'submit';

    public $mode = '';
    public $base_url = '';
    public $query_parameters = [];

    public function toUrl(): string
    {
        return rtrim((string) $this->base_url, '/') . '/?' . http_build_query($this->query_parameters);
    }

    public function stayInTracker(): bool
    {
        return $this->mode !== self::STATE_SUBMIT;
    }
}
