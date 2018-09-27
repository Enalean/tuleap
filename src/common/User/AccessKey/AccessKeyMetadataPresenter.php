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

namespace Tuleap\User\AccessKey;

class AccessKeyMetadataPresenter
{
    public $creation_date;
    public $description;
    public $last_used_on;
    public $last_used_by;

    public function __construct(AccessKeyMetadata $access_key_information)
    {
        $this->creation_date = $access_key_information->getCreationDate()->format(
            $GLOBALS['Language']->getText('system', 'datefmt')
        );
        $this->description   = $access_key_information->getDescription();
        if ($access_key_information->getLastUsedDate() !== null) {
            $this->last_used_on = $access_key_information->getLastUsedDate()->format(
                $GLOBALS['Language']->getText('system', 'datefmt')
            );
        }
        $this->last_used_by = $access_key_information->getLastUsedIP();
    }
}
