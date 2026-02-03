<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Admin;

use Tracker_IDisplayTrackerLayout;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeAssetsGeneric;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\StructureRepresentationBuilder;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Tracker;

final readonly class FieldsUsageDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private RetrieveTracker $tracker_factory,
        private Tracker_IDisplayTrackerLayout $layout,
        private \TemplateRendererFactory $renderer_factory,
        private StructureRepresentationBuilder $structure_representation_builder,
        private FormElementRepresentationsBuilder $form_element_representations_builder,
        private IncludeAssetsGeneric $ckeditor_assets,
    ) {
    }

    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $tracker = $this->getTrackerFromTrackerID((int) $variables['id']);

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
        }

        $layout->includeFooterJavascriptFile($this->ckeditor_assets->getFileURL('ckeditor.js'));
        $layout->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/tracker-admin-fields/frontend-assets',
                    '/assets/trackers/tracker-admin-fields',
                ),
                'src/tracker-admin-fields.ts',
            ),
        );
        $layout->addCssAsset(
            CssViteAsset::fromFileName(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/styles/frontend-assets',
                    '/assets/trackers/styles'
                ),
                'themes/BurningParrot/tracker.scss'
            )
        );

        $tracker->displayAdminItemHeaderBurningParrot($this->layout, 'editformElements', dgettext('tuleap-tracker', 'Manage Field Usage'));
        $this->renderer_factory
            ->getRenderer(__DIR__)
            ->renderToPage(
                'fields-usage',
                FieldsUsageDisplayPresenter::build(
                    $tracker,
                    $current_user,
                    $this->form_element_representations_builder,
                    $this->structure_representation_builder,
                ),
            );
        $tracker->displayFooter($this->layout);
    }

    /**
     * @throws NotFoundException
     */
    private function getTrackerFromTrackerId(int $id): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($id);
        if ($tracker === null) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'That tracker does not exist.'));
        }

        return $tracker;
    }

    public static function getUrl(Tracker $tracker): string
    {
        return '/trackers/' . $tracker->getId() . '/fields';
    }
}
