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
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class AttachmentDownloader
{
    public const JIRA_TEMP_FOLDER = 'jira_import';

    public function __construct(private JiraClient $client, private LoggerInterface $logger, private AttachmentNameGenerator $name_generator)
    {
    }

    public function downloadAttachment(Attachment $attachment): string
    {
        $this->logger->debug("GET " . $attachment->getContentUrl());

        if (! is_dir(self::getTmpFolderURL())) {
            mkdir(self::getTmpFolderURL());
        }

        if (! is_dir(self::getTmpFolderURL())) {
            $this->logger->debug(sprintf('%s is not created on filesystem', self::getTmpFolderURL()));
        }

        $random_name = $this->name_generator->getName();
        if (
            file_put_contents(
                self::getTmpFolderURL() . $random_name,
                $this->client->getAttachmentContents($attachment),
            ) === false
        ) {
            $this->logger->debug(sprintf('Impossible to write content into %s', $random_name));
        }

        return $random_name;
    }

    public static function build(JiraClient $client, LoggerInterface $logger): self
    {
        return new self($client, $logger, new RandomAttachmentNameGenerator());
    }

    public static function getTmpFolderURL(): string
    {
        return ForgeConfig::get('tmp_dir') . '/' . self::JIRA_TEMP_FOLDER . '/';
    }
}
