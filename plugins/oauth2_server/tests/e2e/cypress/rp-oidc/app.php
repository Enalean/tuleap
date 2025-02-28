<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\ResourceServerSocketFactory;
use Amp\Socket\ServerTlsContext;
use Http\Adapter\Guzzle7\Client;
use Http\Client\Common\Plugin\LoggerPlugin;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Validator;
use Monolog\Logger;
use Revolt\EventLoop;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2AuthorizationCallbackController;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2InitFlowController;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2TestFlowClientCredentialStorage;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2TestFlowConfigurationStorage;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2TestFlowHTTPClientWithClientCredentialFactory;
use Tuleap\OAuth2Server\E2E\RelyingPartyOIDC\OAuth2TestFlowSecretGenerator;

require_once __DIR__ . '/../../../../../../src/vendor/autoload.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

EventLoop::defer(
    static function () {
        $log_handler = new StreamHandler(Amp\ByteStream\getStdout());
        $log_handler->setFormatter(new ConsoleFormatter());
        $logger_front_channel = new Logger('rp-oidc-front-channel');
        $logger_front_channel->pushHandler($log_handler);
        $logger_back_channel = new Logger('rp-oidc-backchannel');
        $logger_back_channel->pushHandler($log_handler);

        $private_key = openssl_pkey_new();
        $cert        = openssl_csr_new(['commonName' => 'oauth2-server-rp-oidc'], $private_key);
        $cert        = openssl_csr_sign($cert, null, $private_key, 3650, null, random_int(0, PHP_INT_MAX));
        openssl_x509_export($cert, $out);
        $public_part = (string) $out;
        openssl_pkey_export($private_key, $out);
        $private_part = (string) $out;

        $secret_generator          = new OAuth2TestFlowSecretGenerator();
        $client_credential_storage = new OAuth2TestFlowClientCredentialStorage();
        $configuration_storage     = new OAuth2TestFlowConfigurationStorage();

        $cert_file = tmpfile();
        fwrite($cert_file, $public_part . $private_part);
        $cert_file_path = stream_get_meta_data($cert_file)['uri'];
        $logger_front_channel->debug('Certificate generated at ' . $cert_file_path);

        $cert    = new Certificate($cert_file_path);
        $context = (new BindContext())->withTlsContext((new ServerTlsContext())->withDefaultCertificate($cert));

        $server        = new SocketHttpServer(
            $logger_front_channel,
            new ResourceServerSocketFactory(),
            new SocketClientFactory($logger_front_channel),
        );
        $error_handler = new Amp\Http\Server\DefaultErrorHandler();
        $router        = new Amp\Http\Server\Router($server, $logger_front_channel, $error_handler);
        $http_client   = new \Http\Client\Common\PluginClient(
            Client::createWithConfig(['verify' => false]),
            [new LoggerPlugin($logger_back_channel)]
        );
        $router->addRoute(
            'GET',
            '/init-flow',
            new ClosureRequestHandler(
                (new OAuth2InitFlowController(
                    $secret_generator,
                    $client_credential_storage,
                    $http_client,
                    HTTPFactoryBuilder::requestFactory(),
                    $configuration_storage
                ))(...)
            )
        );
        $router->addRoute(
            'GET',
            '/callback',
            new ClosureRequestHandler(
                (new OAuth2AuthorizationCallbackController(
                    $secret_generator,
                    $http_client,
                    new OAuth2TestFlowHTTPClientWithClientCredentialFactory(
                        $http_client,
                        $client_credential_storage
                    ),
                    $client_credential_storage,
                    $configuration_storage,
                    new Parser(new JoseEncoder()),
                    new Sha256(),
                    new Validator(),
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory()
                ))(...)
            )
        );

        $server->expose('0.0.0.0:8443', $context);
        $server->expose('[::]:8443', $context);
        $server->start($router, $error_handler);

        EventLoop::onSignal(
            SIGINT,
            static function (string $watcher_id) use ($server, $cert_file) {
                EventLoop::cancel($watcher_id);
                fclose($cert_file);
                $server->stop();
            }
        );
    }
);

EventLoop::run();
