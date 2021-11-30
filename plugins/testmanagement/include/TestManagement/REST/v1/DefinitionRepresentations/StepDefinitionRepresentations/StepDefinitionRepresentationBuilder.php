<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, <see http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations;

use Codendi_HTMLPurifier;
use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\Artifact\Artifact;

final class StepDefinitionRepresentationBuilder
{
    /**
     * @throws StepDefinitionFormatNotFoundException
     */
    public static function build(Step $step, Artifact $artifact, Codendi_HTMLPurifier $purifier, ContentInterpretor $content_interpretor): StepDefinitionRepresentation
    {
        $description_representation = self::getStepDefinitionTextField(
            $step->getDescriptionFormat(),
            $step->getDescription(),
            $artifact,
            $purifier,
            $content_interpretor
        );

        $expected_results = self::getStepDefinitionTextField(
            $step->getExpectedResultsFormat(),
            $step->getExpectedResults() ?? '',
            $artifact,
            $purifier,
            $content_interpretor
        );

        return new StepDefinitionRepresentation(
            $step->getId(),
            $description_representation->content,
            $description_representation->format,
            $description_representation->commonmark,
            $expected_results->content,
            $expected_results->format,
            $expected_results->commonmark,
            $step->getRank()
        );
    }

    /**
     * @throws StepDefinitionFormatNotFoundException
     */
    private static function getStepDefinitionTextField(
        string $step_format,
        string $step_content,
        Artifact $artifact,
        Codendi_HTMLPurifier $purifier,
        ContentInterpretor $content_interpretor,
    ): StepDefinitionTextField {
        $project_id = (int) $artifact->getTracker()->getGroupId();

        switch ($step_format) {
            case Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT:
            case Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT:
                return new StepDefinitionTextField(
                    $purifier->purifyHTMLWithReferences($step_content, $project_id),
                    $step_format,
                    null
                );
            case Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT:
                return new StepDefinitionTextField(
                    $content_interpretor->getInterpretedContentWithReferences(
                        $step_content,
                        $project_id
                    ),
                    Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                    $purifier->purify($step_content)
                );
        }
        throw new StepDefinitionFormatNotFoundException($step_format);
    }
}
