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
use Psr\Log\LogLevel;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfiguration\InProjectWithoutSidebar\BackToLinkPresenter;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentUser;

final class OpenInOnlyOfficeController implements \Tuleap\Request\DispatchableWithBurningParrot, \Tuleap\Request\DispatchableWithRequest
{
    public function __construct(
        private ProvideCurrentUser $current_user_provider,
        private ProvideOnlyOfficeDocument $only_office_document_provider,
        private \TemplateRenderer $template_renderer,
        private LoggerInterface $logger,
        private JavascriptAssetGeneric $js_asset,
        private Prometheus $prometheus,
        private string $base_url,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->prometheus->increment(
            'plugin_onlyoffice_open_document_total',
            'Total number of open of document in ONLYOFFICE',
        );

        $user = $this->current_user_provider->getCurrentUser();

        $this->only_office_document_provider->getDocument($user, (int) $variables['id'])
            ->match(
                function (OnlyOfficeDocument $document) use ($layout, $request): void {
                    $link_provider = new DocumentLinkProvider($this->base_url, $document->project);

                    $icon_and_name_of_project = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat(
                        $document->project->getIconUnicodeCodepoint()
                    ) . ' ' . $document->project->getPublicName();

                    $layout->addJavascriptAsset($this->js_asset);

                    $item_title = $document->item->getTitle();
                    $page_title = dgettext('tuleap-onlyoffice', 'ONLYOFFICE');
                    if ($item_title !== null && $item_title !== '') {
                        $page_title = "$item_title – $page_title";
                    }

                    $layout->header(
                        HeaderConfigurationBuilder::get($page_title)
                            ->inProjectWithoutSidebar(
                                $document->project,
                                \DocmanPlugin::SERVICE_SHORTNAME,
                                new BackToLinkPresenter(
                                    sprintf(
                                        dgettext('tuleap-onlyoffice', 'Back to %s documents'),
                                        $icon_and_name_of_project,
                                    ),
                                    $link_provider->getShowLinkUrl($document->item),
                                )
                            )
                            ->withBodyClass(['reduce-help-button'])
                            ->build()
                    );

                    $this->template_renderer->renderToPage('open-in-onlyoffice', OpenInOnlyOfficePresenter::fromOnlyOfficeDocument($document));

                    $layout->footer(FooterConfiguration::withoutContent());
                },
                function (Fault $fault): void {
                    Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                    throw new NotFoundException();
                }
            );
    }
}
