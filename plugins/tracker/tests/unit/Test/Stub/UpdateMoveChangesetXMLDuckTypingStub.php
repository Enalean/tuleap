<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use SimpleXMLElement;
use Tracker;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Tracker\XML\Updater\UpdateMoveChangesetXMLDuckTyping;

final class UpdateMoveChangesetXMLDuckTypingStub implements UpdateMoveChangesetXMLDuckTyping
{
    private int $call_count = 0;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function updateFromDuckTypingCollection(
        PFUser $current_user,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        int $submitted_on,
        int $moved_time,
        DuckTypedMoveFieldCollection $field_collection,
        Tracker $source_tracker,
    ): void {
        $this->call_count++;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
