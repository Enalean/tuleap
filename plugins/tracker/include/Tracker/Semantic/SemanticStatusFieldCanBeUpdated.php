<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Semantic;

use Tracker;
use Tuleap\Event\Dispatchable;

class SemanticStatusFieldCanBeUpdated implements Dispatchable
{
    public const NAME = 'semanticStatusFieldCanBeUpdated';

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var bool
     */
    private $field_can_be_updated = true;

    /**
     * @var string
     */
    private $reason = '';

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function fieldCanBeUpdated(): bool
    {
        return $this->field_can_be_updated;
    }

    public function fieldIsNotUpdatable(string $reason): void
    {
        $this->reason               = $reason;
        $this->field_can_be_updated = false;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
