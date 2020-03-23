<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use Psr\EventDispatcher\ListenerProviderInterface;

final class EventSubjectToNotificationListenerProvider implements ListenerProviderInterface
{
    /**
     * @var callable[][]
     * @psalm-var array<class-string<EventSubjectToNotification>,non-empty-array<callable():EventSubjectToNotificationListener>>
     * @psalm-readonly
     */
    private $mapping;

    /**
     * @param callable[][] $mapping
     * @psalm-param array<class-string<EventSubjectToNotification>,non-empty-array<callable():EventSubjectToNotificationListener>> $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @psalm-return array<callable():EventSubjectToNotificationListener>
     * @psalm-mutation-free
     */
    public function getListenersForEvent(object $event): iterable
    {
        return $this->mapping[get_class($event)] ?? [];
    }
}
