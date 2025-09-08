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


namespace Tuleap\OnlyOffice\Save;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Option\Option;

final class OnlyOfficeSaveController extends \Tuleap\Request\DispatchablePSR15Compatible implements \Tuleap\Request\DispatchableWithRequestNoAuthz
{
    public function __construct(
        private OnlyOfficeCallbackResponseParser $callback_response_parser,
        private SaveOnlyOfficeCallbackDocument $only_office_callback_document_saver,
        private JSONResponseBuilder $json_response_builder,
        private LoggerInterface $logger,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $save_token_information = $request->getAttribute(SaveDocumentTokenData::class);
        if (! $save_token_information instanceof SaveDocumentTokenData) {
            $this->logger->debug('ONLYOFFICE callback called without any "save token data", nothing to do');
            return $this->buildSuccessResponse();
        }

        $this->logger->debug(var_export($save_token_information, true));

        $response_content = $request->getBody()->getContents();
        $this->logger->debug($response_content);

        return $this->callback_response_parser
            ->parseCallbackResponseContent($response_content, $save_token_information)
            ->andThen(
                /**
                 * @psalm-param Option<OnlyOfficeCallbackSaveResponseData> $save_response_data
                 * @psalm-return Ok<null>|Err<Fault>
                 */
                function (Option $save_response_data) use ($save_token_information): Ok|Err {
                    return $this->only_office_callback_document_saver->saveDocument($save_token_information, $save_response_data);
                }
            )
            ->match(
                function (): ResponseInterface {
                    return $this->buildSuccessResponse();
                },
                function (Fault $fault): ResponseInterface {
                    Fault::writeToLogger($fault, $this->logger);
                    return $this->buildErrorResponse();
                }
            );
    }

    private function buildSuccessResponse(): ResponseInterface
    {
        return $this->json_response_builder->fromData(['error' => 0]);
    }

    private function buildErrorResponse(): ResponseInterface
    {
        return $this->json_response_builder->fromData(['error' => 1]);
    }
}
