<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use Tracker;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;

/**
 * @psalm-immutable
 */
final class FeedbackFieldCollectorInterfaceStub implements FeedbackFieldCollectorInterface
{
    private function __construct(
        private readonly bool $should_throw_when_it_is_called,
        private readonly array $fields_not_migrated,
        private readonly array $fields_migrated,
        private readonly array $fields_partially_migrated,
    ) {
    }

    /**
     * @param Tracker_FormElement_Field[] $fields_not_migrated
     * @param Tracker_FormElement_Field[] $fields_migrated
     * @param Tracker_FormElement_Field[] $fields_partially_migrated
     */
    public static function withFields(
        array $fields_not_migrated,
        array $fields_migrated,
        array $fields_partially_migrated,
    ): self {
        return new self(false, $fields_not_migrated, $fields_migrated, $fields_partially_migrated);
    }

    public static function withNoExpectedCalls(): self
    {
        return new self(true, [], [], []);
    }

    public function initAllTrackerFieldAsNotMigrated(Tracker $tracker): void
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();
    }

    public function addFieldInNotMigrated(Tracker_FormElement_Field $field): void
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();
    }

    public function addFieldInFullyMigrated(Tracker_FormElement_Field $field): void
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();
    }

    public function addFieldInPartiallyMigrated(Tracker_FormElement_Field $field): void
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsFullyMigrated(): array
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();

        return $this->fields_migrated;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsNotMigrated(): array
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();

        return $this->fields_not_migrated;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsPartiallyMigrated(): array
    {
        $this->checkFeedBackFieldCollectorCanBeCalled();

        return $this->fields_partially_migrated;
    }

    private function checkFeedBackFieldCollectorCanBeCalled(): void
    {
        if (! $this->should_throw_when_it_is_called) {
            return;
        }

        throw new \RuntimeException("Attempted to use FeedbackFieldCollector while it was not expected");
    }
}
