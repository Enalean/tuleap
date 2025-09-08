<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use Http\Adapter\Guzzle7\Client;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\ServerHostname;

final class HealthCheckCommand extends Command
{
    public const NAME = 'healthcheck';
    private RequestFactoryInterface $request_factory;

    public function __construct(RequestFactoryInterface $request_factory)
    {
        parent::__construct(self::NAME);
        $this->request_factory = $request_factory;
    }

    #[\Override]
    protected function configure(): void
    {
        // The command is hidden for now:
        // * not yet sure if this is the right approach and/or the right place to put it
        // * the verification done are minimal at this stage
        $this
            ->setHidden(true)
            ->setDescription('Ensure Tuleap is capable of processing requests');
    }

    /**
     * This is expected to be used in Dockerfile's HEALTHCHECK, not all exit codes are acceptable
     * See https://docs.docker.com/engine/reference/builder/#healthcheck
     * @psalm-return 0|1
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = ServerHostname::hostnameWithHTTPSPort();
        if (strpos($host, ':') === false) {
            $host .= ':443';
        }

        $client = Client::createWithConfig(
            [
                'curl'   => [CURLOPT_RESOLVE => [$host . ':127.0.0.1']],
                'verify' => false,
            ]
        );

        try {
            $response = $client->sendRequest($this->request_factory->createRequest('GET', 'https://' . $host . '/api/version'));
        } catch (\Psr\Http\Client\ClientExceptionInterface $ex) {
            $output->write(OutputFormatter::escape($ex->getMessage()));
            return 1;
        }

        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            $output->write(
                OutputFormatter::escape(
                    sprintf('The REST API does not return a valid response, expected a 200 status code, got %d', $status_code)
                )
            );
            return 1;
        }

        try {
            json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $ex) {
            $output->write(
                sprintf(
                    'The response received from the REST API does not seem to be valid JSON (%s)',
                    OutputFormatter::escape($ex->getMessage())
                )
            );
            return 1;
        }

        return 0;
    }
}
