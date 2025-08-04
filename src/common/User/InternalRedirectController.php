<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\User;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final readonly class InternalRedirectController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private \URLVerification $url_verification,
        private \TemplateRendererFactory $renderer_factory,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $redirect_url = $request->getValidated('return_to', new \Valid_LocalURI(), false);

        if ($redirect_url !== false && ! $this->url_verification->isInternal($redirect_url)) {
            $redirect_url = false;
        }

        $layout->header(HeaderConfigurationBuilder::get(_('Redirecting...'))->build());

        $this->renderer_factory
            ->getRenderer(__DIR__ . '/../../templates/account')
            ->renderToPage('internal-redirect', [
                'redirect_url' => $redirect_url,
            ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
