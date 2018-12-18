<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Tus;

final class GenericTusEventDispatcher implements TusEventDispatcher
{
    private $listeners = [];

    public function __construct(TusEventSubscriber ...$event_subscribers)
    {
        foreach ($event_subscribers as $event_subscriber) {
            if (! isset($this->listeners[$event_subscriber->getInterestedBySubject()])) {
                $this->listeners[$event_subscriber->getInterestedBySubject()] = [$event_subscriber];
                continue;
            }
            $this->listeners[$event_subscriber->getInterestedBySubject()][] = $event_subscriber;
        }
    }

    public function dispatch($subject, \Psr\Http\Message\ServerRequestInterface $request)
    {
        if (! isset($this->listeners[$subject])) {
            return;
        }
        foreach ($this->listeners[$subject] as $subscriber) {
            $subscriber->notify($request);
        }
    }
}
