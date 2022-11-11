<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Log;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class LogEntryTestBuilder
{
    private \PFUser $user;
    private \DateTimeImmutable $when;
    private \Project $project;
    private int $type;

    private function __construct()
    {
        $this->type    = LogEntry::EVENT_ACCESS;
        $this->project = ProjectTestBuilder::aProject()->build();
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->when    = new \DateTimeImmutable();
    }

    public static function buildWithDefaults(): LogEntry
    {
        return self::anEntry()->build();
    }

    public static function anEntry(): self
    {
        return new self();
    }

    public function withType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function build(): LogEntry
    {
        return new LogEntry(
            $this->when,
            $this->user,
            'WAT',
            null,
            null,
            null,
            $this->type,
            null,
            (int) $this->project->getID(),
        );
    }
}
