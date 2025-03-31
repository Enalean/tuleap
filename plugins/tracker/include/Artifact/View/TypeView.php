<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\View;

use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

final readonly class TypeView extends TrackerArtifactView
{
    /** @see TrackerArtifactView::getTitle() */
    public function getTitle(): string
    {
        return dgettext('tuleap-tracker', 'Children');
    }

    /** @see TrackerArtifactView::getIdentifier() */
    public function getIdentifier(): string
    {
        return 'link';
    }

    /** @see TrackerArtifactView::fetch() */
    public function fetch(): string
    {
        $layout = $GLOBALS['HTML'];
        \assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->addJavascriptAsset(new JavascriptViteAsset(
            new IncludeViteAssets(__DIR__ . '/../../../scripts/artifact/frontend-assets', '/assets/trackers/artifact'),
            'src/children-view.ts',
        ));

        $warning_message = dgettext('tuleap-tracker', 'This view will be removed soon, it is replaced by the brand new Links tab');
        return '<div class="feedback_warning">' . $warning_message . '</div><div data-artifact-id="' . $this->artifact->getId() . '" class="artifact-type"></div>';
    }
}
