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
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;

final class SynchronizedFieldFromProgramAndTeamTrackersCollection
{
    /**
     * @var array<int, true>
     */
    private array $synchronized_fields_ids = [];
    /**
     * @var Field[]
     */
    private array $synchronized_fields = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @psalm-readonly
     */
    public function canUserSubmitAndUpdateAllFields(\PFUser $user, ConfigurationErrorsCollector $errors_collector): bool
    {
        $can_submit = true;
        foreach ($this->synchronized_fields as $synchronized_field) {
            if (! $synchronized_field->userCanSubmit($user)) {
                $url = '/plugins/tracker/permissions/fields-by-field/' .
                    urlencode((string) $synchronized_field->getFullField()->getTrackerId()) . '?' . http_build_query(
                        ['selected_id' => $synchronized_field->getId()]
                    );
                $errors_collector->addError(
                    sprintf(
                        dgettext(
                            'tuleap-program_management',
                            "User can not submit the field <a href='%s'>#%d</a> (%s) of tracker #%d"
                        ),
                        $url,
                        $synchronized_field->getId(),
                        $synchronized_field->getFullField()->getLabel(),
                        $synchronized_field->getFullField()->getTrackerId()
                    )
                );
                $can_submit = false;
                if (! $errors_collector->shouldCollectAllIssues()) {
                    $this->logger->debug(
                        sprintf(
                            "User can not submit the field #%d of tracker #%d",
                            $synchronized_field->getId(),
                            $synchronized_field->getFullField()->getTrackerId()
                        )
                    );

                    return $can_submit;
                }
            }
            if (! $synchronized_field->userCanUpdate($user)) {
                $url = '/plugins/tracker/permissions/fields-by-field/' .
                    urlencode((string) $synchronized_field->getFullField()->getTrackerId()) . '?' . http_build_query(
                        ['selected_id' => $synchronized_field->getId()]
                    );
                $errors_collector->addError(
                    sprintf(
                        dgettext(
                            'tuleap-program_management',
                            "User can not update the field <a href='%s'>#%d</a> (%s) of tracker #%d"
                        ),
                        $url,
                        $synchronized_field->getId(),
                        $synchronized_field->getFullField()->getLabel(),
                        $synchronized_field->getFullField()->getTrackerId()
                    )
                );
                $can_submit = false;
                if (! $errors_collector->shouldCollectAllIssues()) {
                    $this->logger->debug(
                        sprintf(
                            "User can not update the field #%d of tracker #%d",
                            $synchronized_field->getId(),
                            $synchronized_field->getFullField()->getTrackerId()
                        )
                    );

                    return $can_submit;
                }
            }
        }

        return $can_submit;
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
        $this->synchronized_fields_ids = $this->synchronized_fields_ids + $synchronized_field_data->getSynchronizedFieldDataIds(
        );
    }
}
