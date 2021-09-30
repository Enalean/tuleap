<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;

final class IterationCreationProcessor implements ProcessIterationCreation
{
    public function __construct(
        private LoggerInterface $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever
    ) {
    }

    public function processCreation(IterationCreation $iteration_creation): void
    {
        $this->logger->debug(
            sprintf(
                'Processing iteration creation with iteration #%d for user #%d',
                $iteration_creation->getIteration()->getId(),
                $iteration_creation->getUser()->getId()
            )
        );

        try {
            $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
                $this->fields_gatherer,
                $this->values_retriever,
                $this->submission_date_retriever,
                $iteration_creation
            );
        } catch (FieldSynchronizationException | MirroredTimeboxReplicationException $exception) {
            $this->logger->error('Error during creation of mirror iterations', ['exception' => $exception]);
            return;
        }

        $this->logger->debug(sprintf('Title value: %s', $source_values->getTitleValue()->getValue()));
    }
}
