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

namespace Tuleap\PrometheusMetrics;

use ForgeConfig;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;

final class MetricsAuthentication implements MiddlewareInterface
{
    private const USERNAME = 'metrics';

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var BasicAuthLoginExtractor
     */
    private $basic_auth_login_extractor;
    /**
     * @var string
     */
    private $config_dir_root;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        BasicAuthLoginExtractor $basic_auth_login_extractor,
        string $config_dir_root
    ) {
        $this->response_factory           = $response_factory;
        $this->basic_auth_login_extractor = $basic_auth_login_extractor;
        $this->config_dir_root            = $config_dir_root;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $credential = $this->extractAuthentication($request);

        if (! $credential->doesCredentialMatch(self::USERNAME, $this->getSecret())) {
            return $this->response_factory->createResponse(401)->withHeader(
                'WWW-Authenticate',
                'Basic realm="' . ForgeConfig::get('sys_name') . ' /metrics authentication"'
            );
        }

        return $handler->handle($request);
    }

    private function extractAuthentication(ServerRequestInterface $request): MetricsAuthCredential
    {
        $credential_set = $this->basic_auth_login_extractor->extract($request);
        if ($credential_set === null) {
            return MetricsAuthCredential::noCredentialSet();
        }

        return MetricsAuthCredential::fromLoginCredentialSet($credential_set);
    }

    private function getSecret(): ConcealedString
    {
        $path = $this->config_dir_root . '/metrics_secret.key';
        if (! file_exists($path)) {
            throw new \RuntimeException('Configuration not complete. Admin should define a metrics_secret.key');
        }
        $secret = trim(file_get_contents($path));
        if (strlen($secret) < 16) {
            throw new \RuntimeException('Configuration not complete. Secret not strong enough (min len 16)');
        }
        return new ConcealedString($secret);
    }
}
