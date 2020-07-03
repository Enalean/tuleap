<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use ForgeConfig;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\BasicAuth;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;

class AttachmentDownloader
{
    public const JIRA_TEMP_FOLDER = 'jira_import';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $factory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $factory)
    {
        $this->client   = $client;
        $this->factory  = $factory;
    }

    public function downloadAttachment(Attachment $attachment): string
    {
        $request = $this->factory->createRequest(
            'GET',
            $attachment->getContentUrl()
        );

        $response = $this->client->sendRequest($request);

        if (! is_dir($this->getTmpFolderURL())) {
            mkdir($this->getTmpFolderURL());
        }

        $random_name = bin2hex(random_bytes(32));
        file_put_contents(
            $this->getTmpFolderURL() . $random_name,
            $response->getBody()->getContents()
        );

        return $random_name;
    }

    public static function build(JiraCredentials $jira_credentials): self
    {
        $client = HttpClientFactory::createClient(
            new AuthenticationPlugin(
                new BasicAuth($jira_credentials->getJiraUsername(), $jira_credentials->getJiraToken()->getString())
            )
        );

        $request_factory = HTTPFactoryBuilder::requestFactory();

        return new self($client, $request_factory);
    }

    private function getTmpFolderURL(): string
    {
        return ForgeConfig::get('tmp_dir') . '/' . self::JIRA_TEMP_FOLDER . '/';
    }
}
