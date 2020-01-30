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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction;

use Transition_PostActionSubFactory;
use Tuleap\Event\Dispatchable;

class GetExternalSubFactoryByNameEvent implements Dispatchable
{
    public const NAME = 'getExternalSubFactoryByNameEvent';

    /**
     * @var string
     */
    private $post_action_short_name;

    public function __construct(string $post_action_short_name)
    {
        $this->post_action_short_name = $post_action_short_name;
    }

    /**
     * @var Transition_PostActionSubFactory|null
     */
    private $factory = null;

    public function getFactory(): ?Transition_PostActionSubFactory
    {
        return $this->factory;
    }

    public function setFactory(Transition_PostActionSubFactory $factory): void
    {
        $this->factory = $factory;
    }

    public function getPostActionShortName(): string
    {
        return $this->post_action_short_name;
    }
}
