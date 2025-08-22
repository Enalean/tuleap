<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Psr\Log\LoggerInterface;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\RetrieveUserById;

final class CanUserFieldValuesBeFullyMovedVerifier implements VerifyUserFieldValuesCanBeFullyMoved
{
    public function __construct(
        private readonly RetrieveUserById $retrieve_user,
    ) {
    }

    public function canAllUserFieldValuesBeMoved(
        \Tuleap\Tracker\FormElement\Field\ListField $source_field,
        \Tuleap\Tracker\FormElement\Field\ListField $destination_field,
        Artifact $artifact,
        LoggerInterface $logger,
    ): bool {
        $last_changeset_value = $source_field->getLastChangesetValue($artifact);
        if (! $last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_List) {
            $logger->error(sprintf('Last changeset value is not a list for field #%d', $source_field->getId()));
            return false;
        }

        $list_field_values = array_values($last_changeset_value->getListValues());

        foreach ($list_field_values as $value) {
            assert($value instanceof Tracker_FormElement_Field_List_Bind_UsersValue);
            $user = $this->retrieve_user->getUserById((int) $value->getId());
            if (! $user || $user->isAnonymous()) {
                $logger->debug(sprintf('User %s not found ', $value->getId()));
                return false;
            }
            if (! $destination_field->checkValueExists((string) $user->getId())) {
                $logger->debug(sprintf('User %s is not a possible value in field #%d (%s)', $value->getId(), $destination_field->getId(), $destination_field->getName()));

                return false;
            }
        }

        return true;
    }
}
