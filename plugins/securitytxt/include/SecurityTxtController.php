<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\SecurityTxt;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\ServerHostname;

final class SecurityTxtController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public const WELL_KNOWN_SECURITY_TXT_HREF = '/.well-known/security.txt';

    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $primary_contact = \ForgeConfig::get(SecurityTxtOptions::CONTACT);
        if (! $primary_contact) {
            return $this->response_factory->createResponse(404)
                ->withHeader('Content-Type', 'text/plain')
                ->withBody($this->stream_factory->createStream('No security.txt file defined'));
        }

        $canonical              = ServerHostname::HTTPSUrl() . self::WELL_KNOWN_SECURITY_TXT_HREF;
        $current_date           = new \DateTimeImmutable();
        $expires                = $current_date->add(new \DateInterval('P1W'))->format(\DateTimeInterface::ATOM);
        $comment_auto_generated = sprintf(
            dgettext('tuleap-securitytxt', 'This file was automatically generated at %s'),
            $current_date->format(\DateTimeInterface::ATOM)
        );

        return $this->response_factory->createResponse(200)
            ->withHeader('Content-Type', 'text/plain;charset=utf-8')
            ->withBody(
                $this->stream_factory->createStream(
                    <<<EOF
                    # $comment_auto_generated
                    Canonical: $canonical
                    Expires: $expires
                    Contact: $primary_contact
                    EOF
                )
            );
    }
}
