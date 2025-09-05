<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\View;

use TemplateRendererFactory;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

final readonly class LinksView extends TrackerArtifactView
{
    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-tracker', 'Links');
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return 'artifact-links';
    }

    #[\Override]
    public function fetch(): string
    {
        $layout = $GLOBALS['HTML'];
        \assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->addJavascriptAsset(new JavascriptViteAsset(
            new IncludeViteAssets(__DIR__ . '/../../../scripts/artifact/frontend-assets', '/assets/trackers/artifact'),
            'src/link-tab/link-tab-view.ts',
        ));

        $field = \Tracker_FormElementFactory::instance()->getAnArtifactLinkField($this->user, $this->artifact->getTracker());
        if (! $field) {
            $presenter = new EmptyStateLinkViewPresenter($this->user, $this->artifact);
            $renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/artifact');
            return $renderer->renderToString(
                'view/link-view-empty-state',
                $presenter
            );
        }

        return '<div data-artifact-id="' . $this->artifact->getId() . '" class="artifact-type"></div>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">' . dgettext('tuleap-tracker', 'Links from and to current artifact') . '</h1>
                    </div>
                    <section class="tlp-pane-section">'
                . $field->fetchOldReadOnlyView($this->artifact, $this->artifact->getValue($field))
                . '</section>
                </div>
            </section>';
    }
}
