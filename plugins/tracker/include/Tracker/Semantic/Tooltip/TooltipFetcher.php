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

namespace Tuleap\Tracker\Semantic\Tooltip;

use Tuleap\Layout\TooltipJSON;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;

final class TooltipFetcher
{
    /**
     * @var OtherSemanticTooltipEntryFetcher[]
     */
    private readonly array $other_semantic_tooltip_entry_fetchers;

    public function __construct(
        private readonly \TemplateRendererFactory $renderer_factory,
        OtherSemanticTooltipEntryFetcher ...$other_semantic_tooltip_entry_fetchers,
    ) {
        $this->other_semantic_tooltip_entry_fetchers = $other_semantic_tooltip_entry_fetchers;
    }

    /**
     * @return Option<TooltipJSON>
     */
    public function fetchArtifactTooltip(Artifact $artifact, TooltipFields $tooltip, \PFUser $user): Option
    {
        if (! $artifact->userCanView($user)) {
            return Option::nothing(TooltipJSON::class);
        }

        $html = '<table>';
        foreach ($this->other_semantic_tooltip_entry_fetchers as $other_semantic) {
            $html .= $other_semantic->fetchTooltipEntry($artifact, $user);
        }
        foreach ($this->getReadableFields($artifact, $tooltip, $user) as $field) {
            $html .= $field->fetchTooltip($artifact);
        }
        $html .= '</table>';

        return Option::fromValue(
            TooltipJSON::fromHtmlTitleAndHtmlBody(
                $this->renderer_factory
                    ->getRenderer(__DIR__ . '/../../../../templates/tooltip/')
                    ->renderToString(
                        'artifact-tooltip-title',
                        ['title' => $artifact->getTitle(), 'xref' => $artifact->getXRef()]
                    ),
                $html
            )->withAccentColor($artifact->getTracker()->getColor()->getName())
        );
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    private function getReadableFields(Artifact $artifact, TooltipFields $tooltip, \PFUser $user): array
    {
        $readable_fields = [];
        foreach ($tooltip->getFields() as $field) {
            if ($field->userCanRead($user)) {
                $readable_fields[] = $field;
            }
        }

        return $readable_fields;
    }
}
