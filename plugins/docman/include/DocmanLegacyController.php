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

use Tuleap\Docman\ExternalLinks\DocmanHTTPControllerProxy;
use Tuleap\Docman\ExternalLinks\ExternalLinkParametersExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final readonly class DocmanLegacyController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private \DocmanPlugin $plugin,
        private ExternalLinkParametersExtractor $link_parameters_extractor,
        private \Docman_ItemDao $dao,
    ) {
    }

    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
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
}
