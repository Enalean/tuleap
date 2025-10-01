<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;

final class ResumeInstance implements InstanceOperation
{
    public const string TOPIC = 'tuleap.mediawiki-standalone.instance-resume';


    private function __construct(private \Project $project)
    {
    }

    public static function fromEvent(WorkerEvent $event, ProjectByIDFactory $project_factory): ?self
    {
        if ($event->getEventName() !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }

        $project = $project_factory->getValidProjectById($payload['project_id']);
        return new self($project);
    }

    #[\Override]
    public function getRequest(RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory): RequestInterface
    {
        return $request_factory->createRequest(
            'POST',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/resume/' . urlencode($this->project->getUnixNameLowerCase())
        );
    }

    #[\Override]
    public function getTopic(): string
    {
        return self::TOPIC;
    }
}
