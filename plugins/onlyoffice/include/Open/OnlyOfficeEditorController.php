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
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\NotFoundException;

final class OnlyOfficeEditorController extends DispatchablePSR15Compatible
{
    public const EDITOR_ASSET_ENDPOINT = '/no-resource-isolation/onlyoffice/editor_assets';

    public function __construct(
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
            throw new NotFoundException();
        }

        $document_server_url_csp_encoded = str_replace([',', ';'], ['%2C', '%3B'], $document_server_url);

        $csp_header  = "default-src 'report-sample'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; sandbox allow-scripts; report-uri /csp-violation;";
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
                        self::EDITOR_ASSET_ENDPOINT  . '?name=' . urlencode((new JavascriptViteAsset($this->assets, 'scripts/onlyoffice-editor.ts'))->getFileURL()),
                        $csp_nonce,
                        $document_server_url
                    )
                ))
            );
    }
}
