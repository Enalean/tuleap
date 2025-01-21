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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;

final readonly class LinksView extends TrackerArtifactView
{
    public function getTitle(): string
    {
        return dgettext('tuleap-tracker', 'Links');
    }

    public function getIdentifier(): string
    {
        return 'artifact-links';
    }

    public function fetch(): string
    {
        $layout = $GLOBALS['HTML'];
        \assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->addJavascriptAsset(new JavascriptAsset(
            new IncludeAssets(__DIR__ . '/../../../scripts/artifact/frontend-assets', '/assets/trackers/artifact'),
            'link-view.js',
        ));

        $field = \Tracker_FormElementFactory::instance()->getAnArtifactLinkField($this->user, $this->artifact->getTracker());
        if (! $field) {
            return '<div class="tlp-card">' . dgettext('tuleap-tracker', 'No links found for artifact.') . '</div>';
        }

        return '<div data-artifact-id="' . $this->artifact->getId() . '" class="artifact-type"></div>'
            . '<div class=tlp-card>'
            . $field->fetchArtifactValueReadOnly($this->artifact, $this->artifact->getValue($field))
            . '</div>';
    }
}
