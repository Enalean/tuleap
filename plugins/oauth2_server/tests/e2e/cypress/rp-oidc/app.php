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

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Status;
use Amp\Http\Server\Response;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\Server;
use Amp\Http\Server\Request;
use Amp\Socket\ServerTlsContext;
use Monolog\Logger;

require_once __DIR__ . '/../../../../../../src/vendor/autoload.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';

Amp\Loop::run(
    static function () {
        $log_handler = new StreamHandler(new ResourceOutputStream(STDOUT));
        $log_handler->setFormatter(new ConsoleFormatter());
        $logger = new Logger('rp-oidc');
        $logger->pushHandler($log_handler);

        $private_key = openssl_pkey_new();
        $cert        = openssl_csr_new(['commonName' => 'oauth2-server-rp-oidc'], $private_key);
        $cert        = openssl_csr_sign($cert, null, $private_key, 3650, null, random_int(0, PHP_INT_MAX));
        openssl_x509_export($cert, $out);
        $public_part = (string) $out;
        openssl_pkey_export($private_key, $out);
        $private_part = (string) $out;

        $state          = bin2hex(random_bytes(32));
        $pkce_challenge = bin2hex(random_bytes(32));

        $cert_file = tmpfile();
        fwrite($cert_file, $public_part . $private_part);
        $cert_file_path = stream_get_meta_data($cert_file)['uri'];
        $logger->debug('Certificate generated at ' . $cert_file_path);

        $cert    = new Certificate($cert_file_path);
        $context = (new BindContext())->withTlsContext((new ServerTlsContext())->withDefaultCertificate($cert));

        $sockets = [Server::listen('0.0.0.0:8443', $context), Server::listen('[::]:8443', $context)];

        $router = new Amp\Http\Server\Router();
        $router->addRoute(
            'GET',
            '/init-flow',
            new CallableRequestHandler(
                static function (Request $request) use ($state, $pkce_challenge): Response {
                    parse_str($request->getUri()->getQuery(), $query_params);
                    $redirect_parameters = [
                        'response_type'         => 'code',
                        'client_id'             => $query_params['client_id'],
                        'client_secret'         => $query_params['client_secret'],
                        'scope'                 => 'openid offline_access',
                        'redirect_uri'          => 'https://oauth2-server-rp-oidc:8443/callback',
                        'state'                 => $state,
                        'code_challenge'        => sodium_bin2base64(hash('sha256', $pkce_challenge, true), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                        'code_challenge_method' => 'S256'
                    ];
                    return new Response(
                        Status::FOUND,
                        ['Location' => 'https://tuleap/oauth2/authorize?' . http_build_query($redirect_parameters)]
                    );
                }
            )
        );
        $router->addRoute(
            'GET',
            '/callback',
            new CallableRequestHandler(
                static function (Request $request) use ($state): Response {
                    parse_str($request->getUri()->getQuery(), $query_params);
                    if ($query_params['state'] !== $state) {
                        return new Response(
                            Status::BAD_REQUEST,
                            ['Content-Type' => 'text/html'],
                            'Failure, state does not match'
                        );
                    }
                    return new Response(Status::OK, ['Content-Type' => 'text/html'], 'OK');
                }
            )
        );
        $server = new HttpServer($sockets, $router, $logger);

        yield $server->start();

        Amp\Loop::onSignal(
            SIGINT,
            static function (string $watcher_id) use ($server, $cert_file) {
                Amp\Loop::cancel($watcher_id);
                fclose($cert_file);
                yield $server->stop();
            }
        );
    }
);
