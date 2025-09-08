<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Tooltip\OtherSemantic;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Tooltip\OtherSemanticTooltipEntryFetcher;

final class ProgressTooltipEntry implements OtherSemanticTooltipEntryFetcher
{
    public function __construct(
        private readonly SemanticProgressBuilder $progress_builder,
        private readonly \TemplateRendererFactory $renderer_factory,
    ) {
    }

    #[\Override]
    public function fetchTooltipEntry(Artifact $artifact, \PFUser $user): string
    {
        $semantic            = $this->progress_builder->getSemantic($artifact->getTracker());
        $progress_calculator = $semantic->getComputationMethod();
        if (! $progress_calculator->isConfigured()) {
            return '';
        }

        $progress_result = $progress_calculator->computeProgression($artifact, $user);
        $renderer        = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/tooltip/other-semantic/');

        return $renderer->renderToString('progress-tooltip-entry', [
            'percentage'             => round(max(0, min(100, $progress_result->getValue() * 100))),
            'progress_error_message' => $progress_result->getErrorMessage(),
        ]);
    }
}
