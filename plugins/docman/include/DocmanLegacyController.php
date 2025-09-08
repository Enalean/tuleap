<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use HTTPRequest;
use Tuleap\Docman\ExternalLinks\DocmanHTTPControllerProxy;
use Tuleap\Docman\ExternalLinks\ExternalLinkParametersExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithThemeSelection;

final class DocmanLegacyController implements DispatchableWithRequest, DispatchableWithThemeSelection
{
    /**
     * @var \DocmanPlugin
     */
    private $plugin;
    /**
     * @var ExternalLinkParametersExtractor
     */
    private $link_parameters_extractor;
    /**
     * @var \Docman_ItemDao
     */
    private $dao;

    public function __construct(
        \DocmanPlugin $plugin,
        ExternalLinkParametersExtractor $link_parameters_extractor,
        \Docman_ItemDao $dao,
    ) {
        $this->plugin                    = $plugin;
        $this->link_parameters_extractor = $link_parameters_extractor;
        $this->dao                       = $dao;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        (
            new DocmanHTTPControllerProxy(
                $this->link_parameters_extractor,
                new \Docman_HTTPController(
                    $this->plugin,
                    $this->plugin->getPluginPath(),
                    $this->plugin->getThemePath(),
                    $request
                ),
                $this->dao
            )
        )->process($request, $request->getCurrentUser());
    }

    #[\Override]
    public function isInABurningParrotPage(HTTPRequest $request, array $variables): bool
    {
        return in_array(
            $request->get('action'),
            [
                \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                \Docman_View_Admin_MetadataDetailsUpdateLove::IDENTIFIER,
                \Docman_View_Admin_Metadata::IDENTIFIER,
                \Docman_View_Admin_LockInfos::IDENTIFIER,
                \Docman_View_Admin_Obsolete::IDENTIFIER,
                \Docman_View_Admin_View::IDENTIFIER,
                \Docman_View_Admin_Permissions::IDENTIFIER,
                'admin',
                \Docman_View_Admin_FilenamePattern::IDENTIFIER,
            ],
            true,
        );
    }
}
