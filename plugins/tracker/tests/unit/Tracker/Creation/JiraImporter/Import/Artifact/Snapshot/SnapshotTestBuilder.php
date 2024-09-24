<?php
/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot;

use Tuleap\Test\Builders\UserTestBuilder;

final class SnapshotTestBuilder
{
    private \DateTimeImmutable $date;
    private FieldSnapshot $snapshot;

    private function __construct()
    {
        $this->date     = new \DateTimeImmutable('2024-09-19 20:30:01');
        $this->snapshot = FieldSnapshotTestBuilder::aTextFieldSnapshot()->withValue('aaaaaaaaaa')->build();
    }

    public static function aSnapshot(): SnapshotTestBuilder
    {
        return new self();
    }

    public function build(): Snapshot
    {
        return new Snapshot(
            UserTestBuilder::aUser()->build(),
            $this->date,
            [
                $this->snapshot,
            ],
            null,
        );
    }

    public function withDate(string $date): SnapshotTestBuilder
    {
        $this->date = new \DateTimeImmutable($date);
        return $this;
    }

    public function withSnapshot(FieldSnapshot $snapshot): SnapshotTestBuilder
    {
        $this->snapshot = $snapshot;
        return $this;
    }
}
