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

namespace Tuleap\MediawikiStandalone\Instance;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;

final class LogUsersOutInstance implements InstanceOperation
{
    public const string TOPIC = 'tuleap.mediawiki-standalone.instance-log-users-out';

    private function __construct(private ?\Project $project, private ?int $user_id)
    {
    }

    public static function fromEvent(WorkerEvent $event, ProjectByIDFactory $project_factory): ?self
    {
        if ($event->getEventName() !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        $project = null;
        if (isset($payload['project_id']) && is_int($payload['project_id'])) {
            $project = $project_factory->getValidProjectById($payload['project_id']);
        }

        $user_id = null;
        if (isset($payload['user_id']) && is_int($payload['user_id'])) {
            $user_id = $payload['user_id'];
        }

        return new self($project, $user_id);
    }

    #[\Override]
    public function getRequest(RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory): RequestInterface
    {
        return $request_factory->createRequest(
            'POST',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/maintenance/' . urlencode($this->getInstanceNameQualifier()) . '/terminate-sessions'
        )->withBody($stream_factory->createStream($this->getUserSelectorJSONBody()));
    }

    private function getInstanceNameQualifier(): string
    {
        if ($this->project === null) {
            return '*';
        }

        return $this->project->getUnixNameLowerCase();
    }

    private function getUserSelectorJSONBody(): string
    {
        if ($this->user_id === null) {
            return '{}';
        }

        return json_encode(['user' => (string) $this->user_id], JSON_THROW_ON_ERROR);
    }

    #[\Override]
    public function getTopic(): string
    {
        return self::TOPIC;
    }
}
