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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Events\CanSubmitNewArtifactEvent;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;

final class CanSubmitNewArtifactHandler
{
    private ConfigurationErrorsGatherer $configuration_errors_gatherer;

    public function __construct(ConfigurationErrorsGatherer $configuration_errors_gatherer)
    {
        $this->configuration_errors_gatherer = $configuration_errors_gatherer;
    }

    public function handle(
        CanSubmitNewArtifactEvent $event,
        ConfigurationErrorsCollector $errors_collector,
    ): void {
        $this->configuration_errors_gatherer->gatherConfigurationErrors(
            $event->getTrackerReference(),
            $event->getUserIdentifier(),
            $errors_collector
        );

        if ($errors_collector->hasError()) {
            $event->disableArtifactSubmission();
        }
    }
}
