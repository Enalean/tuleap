<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Admin;

use Tracker;
use Tuleap\Tracker\TrackerIsInvalidException;

class TrackerGeneralSettingsChecker
{
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;

    public function __construct(\TrackerFactory $tracker_factory, \ReferenceManager $reference_manager)
    {
        $this->tracker_factory   = $tracker_factory;
        $this->reference_manager = $reference_manager;
    }

    /**
     * @throws TrackerIsInvalidException
     */
    public function check(
        int $project_id,
        string $previous_shortname,
        string $previous_public_name,
        string $validated_public_name,
        string $validated_tracker_color,
        string $validated_short_name
    ): void {
        if (strlen($previous_shortname) < Tracker::MAX_TRACKER_SHORTNAME_LENGTH && strlen($validated_short_name) > Tracker::MAX_TRACKER_SHORTNAME_LENGTH) {
            throw TrackerIsInvalidException::buildInvalidLength();
        }

        if (! $validated_public_name || ! $validated_tracker_color || ! $validated_short_name) {
            throw TrackerIsInvalidException::buildMissingRequiredProperties();
        }

        if ($previous_public_name !== $validated_public_name) {
            if ($this->tracker_factory->isNameExists($validated_public_name, $project_id)) {
                throw TrackerIsInvalidException::nameAlreadyExists($validated_public_name);
            }
        }

        if ($previous_shortname !== $validated_short_name) {
            if (! $this->itemNameIsValid($validated_short_name)) {
                throw  TrackerIsInvalidException::shortnameIsInvalid($validated_short_name);
            }

            if ($this->tracker_factory->isShortNameExists($validated_short_name, $project_id)) {
                throw  TrackerIsInvalidException::shortnameAlreadyExists($validated_short_name);
            }

            if (! $this->reference_manager->checkKeyword($validated_short_name)) {
                throw  TrackerIsInvalidException::shortnameIsInvalid($validated_short_name);
            }

            if ($this->reference_manager->_isKeywordExists($validated_short_name, $project_id)) {
                throw  TrackerIsInvalidException::shortnameAlreadyExists($validated_short_name);
            }
        }
    }

    private function itemNameIsValid(string $item_name): bool
    {
        return (bool) preg_match("/^[a-zA-Z0-9_]+$/i", $item_name);
    }
}
