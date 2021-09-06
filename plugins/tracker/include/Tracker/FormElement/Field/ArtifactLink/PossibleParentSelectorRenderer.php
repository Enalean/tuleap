<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Artifact\Renderer\ListPickerIncluder;

class PossibleParentSelectorRenderer
{
    private function __construct(private \TemplateRenderer $renderer)
    {
    }

    public static function buildWithDefaultTemplateRenderer(): self
    {
        return new self(\TemplateRendererFactory::build()->getRenderer(__DIR__ . '/templates'));
    }

    public function render(string $form_name_prefix, string $prefill_parent, PossibleParentSelector $possible_parent_selector): string
    {
        if (! $possible_parent_selector->isSelectorDisplayed()) {
            return '';
        }

        $possible_parent_presenters = [];
        if ($possible_parent_selector->getPossibleParents()) {
            foreach ($possible_parent_selector->getPossibleParents()->getArtifacts() as $possible_parent) {
                $possible_parent_presenters[] = new PossibleParentPresenter(
                    $possible_parent->getId(),
                    $possible_parent->getXRef(),
                    $possible_parent->getTitle() ?? '',
                    $prefill_parent !== '' && $possible_parent->getId() === (int) $prefill_parent,
                );
            }
        }

        ListPickerIncluder::includeArtifactLinksListPickerAssets($possible_parent_selector->tracker->getId());
        return $this->renderer->renderToString(
            'possible-parent-selector',
            new PossibleParentSelectorPresenter(
                $possible_parent_selector->getParentLabel(),
                $possible_parent_selector->getLabel(),
                $form_name_prefix,
                $possible_parent_selector->canCreate(),
                ...$possible_parent_presenters,
            )
        );
    }
}
