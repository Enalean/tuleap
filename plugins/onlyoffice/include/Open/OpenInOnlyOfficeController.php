<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use HTTPRequest;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\NotFoundException;

final class OpenInOnlyOfficeController implements \Tuleap\Request\DispatchableWithBurningParrot, \Tuleap\Request\DispatchableWithRequest
{
    public function __construct(
        private \UserManager $user_manager,
        private \Docman_ItemFactory $item_factory,
        private \Docman_VersionFactory $version_factory,
        private IncludeViteAssets $assets,
        private Prometheus $prometheus,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->prometheus->increment(
            'plugin_onlyoffice_open_document_total',
            'Total number of open of document in ONLYOFFICE',
        );

        $item = $this->item_factory->getItemFromDb((int) $variables['id']);
        if (! $item instanceof \Docman_File) {
            throw new NotFoundException();
        }

        $user = $this->user_manager->getCurrentUser();

        $docman_permissions_manager = \Docman_PermissionsManager::instance($item->getGroupId());
        if (! $docman_permissions_manager->userCanAccess($user, $item->getId())) {
            throw new NotFoundException();
        }

        $version = $this->version_factory->getCurrentVersionForItem($item);
        if ($version === null) {
            throw new NotFoundException();
        }
        if (! AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice($version->getFilename())) {
            throw new NotFoundException();
        }

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->assets, 'scripts/open-in-onlyoffice.ts'));
        $layout->header(
            HeaderConfiguration::inProjectWithoutSidebar(dgettext('tuleap-onlyoffice', 'ONLYOFFICE'))
        );

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates');
        $renderer->renderToPage('open-in-onlyoffice', OpenInOnlyOfficePresenter::fromDocmanFile($item));

        $layout->footer(FooterConfiguration::withoutContent());
    }
}
