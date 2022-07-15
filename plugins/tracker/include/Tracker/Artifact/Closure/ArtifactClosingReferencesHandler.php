<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use Psr\Log\LoggerInterface;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Reference\ExtractReferences;
use Tuleap\Reference\ReferenceInstance;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactClosingReferencesHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private ExtractReferences $reference_extractor,
    ) {
    }

    public function handlePotentialReferencesReceived(PotentialReferencesReceived $event): void
    {
        $reference_instances = $this->reference_extractor->extractReferences(
            $event->text_with_potential_references,
            (int) $event->project->getID()
        );
        foreach ($reference_instances as $instance) {
            $this->handleSingleReference($event, $instance);
        }
    }

    private function handleSingleReference(PotentialReferencesReceived $event, ReferenceInstance $instance): void
    {
        if ($instance->getReference()->getNature() !== Artifact::REFERENCE_NATURE) {
            return;
        }
        if ((int) $event->project->getID() !== (int) $instance->getReference()->getGroupId()) {
            return;
        }
        $closing_keyword = ClosingKeyword::fromString($instance->getContextWord());
        if (! $closing_keyword) {
            return;
        }
        $this->logger->debug(sprintf('Found reference %s with closing keyword', $instance->getMatch()));
    }
}
