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

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherWithFallback implements EventDispatcherInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EventDispatcherInterface
     */
    private $primary_dispatcher;
    /**
     * @var EventDispatcherInterface
     */
    private $secondary_dispatcher;

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $primary_dispatcher,
        EventDispatcherInterface $secondary_dispatcher
    ) {
        $this->logger               = $logger;
        $this->primary_dispatcher   = $primary_dispatcher;
        $this->secondary_dispatcher = $secondary_dispatcher;
    }

    public function dispatch(object $event): object
    {
        try {
            return $this->primary_dispatcher->dispatch($event);
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Dispatching to primary dispatcher %s failed with %s: %s',
                    get_class($this->primary_dispatcher),
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
            return $this->secondary_dispatcher->dispatch($event);
        }
    }
}
