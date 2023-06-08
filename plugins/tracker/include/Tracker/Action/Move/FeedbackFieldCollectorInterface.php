<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action\Move;

use Tracker;
use Tracker_FormElement_Field;

interface FeedbackFieldCollectorInterface
{
    public function initAllTrackerFieldAsNotMigrated(Tracker $tracker): void;

    public function addFieldInNotMigrated(Tracker_FormElement_Field $field): void;

    public function addFieldInFullyMigrated(Tracker_FormElement_Field $field): void;

    public function addFieldInPartiallyMigrated(Tracker_FormElement_Field $field): void;

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsFullyMigrated(): array;

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsNotMigrated(): array;

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsPartiallyMigrated(): array;
}
