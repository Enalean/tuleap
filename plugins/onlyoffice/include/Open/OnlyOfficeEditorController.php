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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Fault;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;
use Tuleap\OnlyOffice\Open\Editor\ProvideOnlyOfficeGlobalEditorJWToken;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentUser;

final class OnlyOfficeEditorController extends DispatchablePSR15Compatible
{
    public function __construct(
        private LoggerInterface $logger,
        private ProvideOnlyOfficeGlobalEditorJWToken $onlyoffice_global_editor_jwt_provider,
        private ProvideCurrentUser $current_user_provider,
        private \TemplateRenderer $template_renderer,
        private IncludeViteAssets $assets,
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);
        $csp_nonce = $layout->getCSPNonce();

        $document_server_url = \ForgeConfig::get(OnlyOfficeDocumentServerSettings::URL, null);
        if ($document_server_url === null) {
            $this->logger->debug(sprintf('Settings %s does not seem to be defined', OnlyOfficeDocumentServerSettings::URL));
            throw new NotFoundException();
        }

        $item_id = (int) $request->getAttribute('id');

        return $this->onlyoffice_global_editor_jwt_provider->getGlobalEditorJWToken(
            $this->current_user_provider->getCurrentUser(),
            $item_id,
            new \DateTimeImmutable()
        )->match(
            function (string $config_token) use ($document_server_url, $csp_nonce): ResponseInterface {
                $document_server_url_csp_encoded = str_replace([',', ';'], ['%2C', '%3B'], $document_server_url);

                $csp_header  = "default-src 'report-sample'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; sandbox allow-scripts allow-same-origin allow-downloads; report-uri /csp-violation;";
                $csp_header .= "style-src 'nonce-$csp_nonce'; script-src 'nonce-$csp_nonce' 'strict-dynamic'; frame-src $document_server_url_csp_encoded;";

                return $this->response_factory->createResponse()
                    ->withHeader(
                        'Content-Security-Policy',
                        $csp_header
                    )
                    ->withBody(
                        $this->stream_factory->createStream($this->template_renderer->renderToString(
                            'editor',
                            new OnlyOfficeEditorPresenter(
                                (new JavascriptViteAsset($this->assets, 'scripts/onlyoffice-editor.ts'))->getFileURL(),
                                $csp_nonce,
                                $document_server_url,
                                $config_token,
                            )
                        ))
                    );
            },
            function (Fault $fault): void {
                Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                throw new NotFoundException();
            }
        );
    }
}
