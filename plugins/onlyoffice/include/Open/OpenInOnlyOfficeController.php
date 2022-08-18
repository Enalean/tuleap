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
use Psr\Log\LoggerInterface;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentUser;

final class OpenInOnlyOfficeController implements \Tuleap\Request\DispatchableWithBurningParrot, \Tuleap\Request\DispatchableWithRequest
{
    public function __construct(
        private ProvideCurrentUser $current_user_provider,
        private ProvideDocmanFileLastVersion $docman_file_last_version_provider,
        private \TemplateRenderer $template_renderer,
        private LoggerInterface $logger,
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

        $user = $this->current_user_provider->getCurrentUser();

        $this->docman_file_last_version_provider->getLastVersionOfAFileUserCanAccess($user, (int) $variables['id'])
            ->andThen(
                /** @psalm-return Ok<\Docman_Version>|Err<Fault> */
                function (\Docman_Version $version): Ok|Err {
                    if (! AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice($version->getFilename())) {
                        return Result::err(Fault::fromMessage(sprintf('Item #%d cannot be opened with ONLYOFFICE', $version->getItemId())));
                    }
                    return Result::ok($version);
                }
            )->match(
                function (\Docman_Version $version) use ($layout): void {
                    $layout->addJavascriptAsset(new JavascriptViteAsset($this->assets, 'scripts/open-in-onlyoffice.ts'));
                    $layout->header(
                        HeaderConfiguration::inProjectWithoutSidebar(dgettext('tuleap-onlyoffice', 'ONLYOFFICE'))
                    );

                    $this->template_renderer->renderToPage('open-in-onlyoffice', OpenInOnlyOfficePresenter::fromDocmanVersion($version));

                    $layout->footer(FooterConfiguration::withoutContent());
                },
                function (Fault $fault): void {
                    $this->logger->debug((string) $fault);
                    throw new NotFoundException();
                }
            );
    }
}
