<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\VerifyFieldPermissions;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FieldPermissionsVerifier implements VerifyFieldPermissions
{
    public function __construct(private RetrieveUser $retrieve_user, private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    #[\Override]
    public function canUserSubmit(
        UserIdentifier $user_identifier,
        TitleFieldReference|DescriptionFieldReference|StatusFieldReference|StartDateFieldReference|DurationFieldReference|EndDateFieldReference|ArtifactLinkFieldReference $field,
    ): bool {
        $user       = $this->retrieve_user->getUserWithId($user_identifier);
        $full_field = $this->form_element_factory->getFieldById($field->getId());

        if (! $full_field) {
            throw new FieldNotFoundException($field->getId());
        }

        return $full_field->userCanSubmit($user);
    }

    #[\Override]
    public function canUserUpdate(
        UserIdentifier $user_identifier,
        TitleFieldReference|DescriptionFieldReference|StatusFieldReference|StartDateFieldReference|DurationFieldReference|EndDateFieldReference|ArtifactLinkFieldReference $field,
    ): bool {
        $user       = $this->retrieve_user->getUserWithId($user_identifier);
        $full_field = $this->form_element_factory->getFieldById($field->getId());

        if (! $full_field) {
            throw new FieldNotFoundException($field->getId());
        }

        return $full_field->userCanUpdate($user);
    }
}
