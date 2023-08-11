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
 *
 */

declare(strict_types=1);

namespace Tuleap\Log;

use ForgeConfig;
use Gelf\Publisher;
use Gelf\Transport\AbstractTransport;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use Gelf\Transport\SslOptions;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\TransportInterface;
use Monolog\Handler\GelfHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Psr\Log\LoggerInterface;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Config\ConfigKey;

final class LogToGraylog2
{
    public const CONFIG_LOGGER_GRAYLOG2 = 'graylog2';

    #[ConfigKey("Graylog2 server fully qualified domain name")]
    public const CONFIG_GRAYLOG2_SERVER = 'graylog2_server';

    #[ConfigKey("Graylog2 port")]
    public const CONFIG_GRAYLOG2_PORT = 'graylog2_port';

    #[ConfigKey("Toggle usage of TLS to communicate with Graylog2 server")]
    public const CONFIG_GRAYLOG2_SSL = 'graylog2_ssl';

    #[ConfigKey("Toggle debug mode of communication between Tuleap and Graylog2 server")]
    public const CONFIG_GRAYLOG2_DEBUG = 'graylog2_debug';

    public function configure(Logger $logger, int|Level $level): LoggerInterface
    {
        $server = ForgeConfig::get(self::CONFIG_GRAYLOG2_SERVER);
        $port   = ForgeConfig::getInt(self::CONFIG_GRAYLOG2_PORT);
        if ($server === false || $port === 0) {
            throw new UnableToSetupHandlerException(self::CONFIG_LOGGER_GRAYLOG2, 'Server or port not configured');
        }

        $logger->pushHandler(
            new GelfHandler(
                new Publisher($this->getTransport($server, $port)),
                $level,
            )
        );

        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->pushProcessor(new IncludeBacktraceProcessor());
        $logger->pushProcessor(new TuleapIdentifierProcessor(VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence())));

        return $logger;
    }

    private function getTransport(string $server, int $port): TransportInterface
    {
        if (ForgeConfig::getInt(self::CONFIG_GRAYLOG2_DEBUG) === 1) {
            return $this->getTcpTransport($server, $port);
        }
        return new IgnoreErrorTransportWrapper($this->getTcpTransport($server, $port));
    }

    private function getTcpTransport(string $server, int $port): AbstractTransport
    {
        $ssl_options = null;
        if (ForgeConfig::getInt(self::CONFIG_GRAYLOG2_SSL) === 1) {
            $ssl_options = new SslOptions();
            $ssl_options->setCaFile('/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
        }

        return new TcpTransport($server, $port, $ssl_options);
    }
}
