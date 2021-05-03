<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Psr\Log\LoggerInterface;

final class SynchronizedFieldFromProgramAndTeamTrackersCollection
{
    /**
     * @var array<int, true>
     */
    private $synchronized_fields_ids = [];
    /**
     * @var Field[]
     */
    private $synchronized_fields = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @psalm-readonly
     */
    public function canUserSubmitAndUpdateAllFields(\PFUser $user): bool
    {
        foreach ($this->synchronized_fields as $synchronized_field) {
            if (! $synchronized_field->userCanSubmit($user)) {
                $this->logger->debug(
                    sprintf(
                        "User can not submit the field #%d (%s) of tracker #%d",
                        $synchronized_field->getId(),
                        $synchronized_field->getFullField()->getLabel(),
                        $synchronized_field->getFullField()->getTrackerId()
                    )
                );
                return false;
            }
            if (! $synchronized_field->userCanUpdate($user)) {
                $this->logger->debug(
                    sprintf(
                        "User can not update the field #%d (%s) of tracker #%d",
                        $synchronized_field->getId(),
                        $synchronized_field->getFullField()->getLabel(),
                        $synchronized_field->getFullField()->getTrackerId()
                    )
                );
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-readonly
     */
    public function isFieldSynchronized(\Tracker_FormElement_Field $field): bool
    {
        return isset($this->synchronized_fields_ids[$field->getId()]);
    }

    /**
     * @return int[]
     * @psalm-readonly
     */
    public function getSynchronizedFieldIDs(): array
    {
        return array_keys($this->synchronized_fields_ids);
    }

    public function add(SynchronizedFieldFromProgramAndTeamTrackers $synchronized_field_data): void
    {
        $this->synchronized_fields     = array_merge(
            $this->synchronized_fields,
            $synchronized_field_data->getSynchronizedFieldsData()->getAllFields()
        );
        $this->synchronized_fields_ids = $this->synchronized_fields_ids + $synchronized_field_data->getSynchronizedFieldDataIds();
    }
}
