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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\Attachment;

final class JiraClientReplay implements JiraClient
{
    private array $payloads = [];

    private function __construct(
        string $log_dir,
        private readonly bool $is_cloud,
        private readonly bool $is_jira_server_9,
    ) {
        foreach (file($log_dir . '/manifest.log', FILE_IGNORE_NEW_LINES) as $line) {
            [$url, $file, $code] = explode(' ', $line);
            if ($code === '200') {
                $url_chunks = parse_url($url);
                $sub_url    = '';
                if (isset($url_chunks['path'])) {
                    $sub_url .= $url_chunks['path'];
                }
                if (isset($url_chunks['query'])) {
                    $sub_url .= '?' . $url_chunks['query'];
                }
                if (isset($url_chunks['fragment'])) {
                    $sub_url .= '#' . $url_chunks['fragment'];
                }
                $this->payloads[$sub_url] = $this->getResponse($log_dir . '/' . $file);
            }
        }
    }

    public static function buildJira7And8Server(string $log_dir): self
    {
        return new self($log_dir, false, false);
    }

    public static function buildJira9Server(string $log_dir): self
    {
        return new self($log_dir, false, true);
    }

    public static function buildJiraCloud(string $log_dir): self
    {
        return new self($log_dir, true, false);
    }

    public function getJiraProject(): ?string
    {
        foreach ($this->payloads as $url => $payload) {
            $match = [];
            if (preg_match('%^/rest/api/2/project/(.*)/statuses$%', $url, $match) === 1) {
                return $match[1];
            }
        }
        return null;
    }

    public function getJiraIssueTypeId(): ?string
    {
        foreach ($this->payloads as $url => $payload) {
            $match = [];
            if (preg_match('%^/rest/api/2/issuetype/(.*)$%', $url, $match) === 1) {
                return $match[1];
            }
        }
        return null;
    }

    private function getResponse(string $file_path): string
    {
        $file_contents = file_get_contents($file_path);

        $marker_pos = strpos($file_contents, ClientWrapper::DEBUG_MARKER_BODY);
        if ($marker_pos === false) {
            return $file_contents;
        }

        return substr($file_contents, $marker_pos + strlen(ClientWrapper::DEBUG_MARKER_BODY));
    }

    public function getUrl(string $url): ?array
    {
        if (isset($this->payloads[$url])) {
            return \json_decode($this->payloads[$url], true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException('REST call not covered: ' . $url);
    }

    public function isJiraCloud(): bool
    {
        return $this->is_cloud;
    }

    public function getAttachmentContents(Attachment $attachment): string
    {
        return 'fake data';
    }

    public function isJiraServer9(): bool
    {
        return $this->is_jira_server_9;
    }
}
