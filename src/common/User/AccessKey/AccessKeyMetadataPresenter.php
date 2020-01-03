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

declare(strict_types=1);

namespace Tuleap\User\AccessKey;

/**
 * @psalm-immutable
 */
class AccessKeyMetadataPresenter
{
    public $id;
    public $creation_date;
    public $description;
    public $last_used_on;
    public $last_used_by;
    public $expiration_date;
    /**
     * @var AccessKeyScopePresenter[]
     */
    public $scopes = [];

    public function __construct(AccessKeyMetadata $access_key_information)
    {
        $this->id            = $access_key_information->getID();
        $this->creation_date = $access_key_information->getCreationDate()->format(
            $GLOBALS['Language']->getText('system', 'datefmt')
        );
        $this->description   = $access_key_information->getDescription();

        $last_used_date = $access_key_information->getLastUsedDate();
        if ($last_used_date !== null) {
            $this->last_used_on = $last_used_date->format(
                $GLOBALS['Language']->getText('system', 'datefmt')
            );
        }

        $expiration_date = $access_key_information->getExpirationDate();
        if ($expiration_date !== null) {
            $this->expiration_date = $expiration_date->format(
                $GLOBALS['Language']->getText('system', 'datefmt')
            );
        }

        $this->last_used_by = $access_key_information->getLastUsedIP();

        foreach ($access_key_information->getScopes() as $scope) {
            $this->scopes[] = new AccessKeyScopePresenter($scope->getDefinition());
        }
    }
}
