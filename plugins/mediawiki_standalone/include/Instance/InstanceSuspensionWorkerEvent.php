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

use Tuleap\Queue\QueueTask;
use Tuleap\Queue\WorkerEvent;

class InstanceSuspensionWorkerEvent implements QueueTask
{
    public const TOPIC = 'tuleap.mediawiki-standalone.instance-suspension';

    public function __construct(public int $project_id)
    {
    }

    public static function fromEvent(WorkerEvent $event): ?self
    {
        if ($event->getEventName() !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }
        return new self($payload['project_id']);
    }

    public function getTopic(): string
    {
        return self::TOPIC;
    }

    public function getPayload(): array
    {
        return ['project_id' => $this->project_id];
    }

    public function getPreEnqueueMessage(): string
    {
        return sprintf('Enqueue suspension of mediawiki instance for %d', $this->project_id);
    }
}
