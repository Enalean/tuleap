<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;

final class BuildProgramPrivacyStub implements BuildProgramPrivacy
{
    private function __construct(private ProgramPrivacy $privacy)
    {
    }

    public static function withPrivateAccess(): self
    {
        return new self(
            ProgramPrivacy::fromPrivacy(
                false,
                false,
                true,
                false,
                false,
                'It is private, please go away',
                'Private',
                'Guinea Pig'
            )
        );
    }

    #[\Override]
    public function build(ProgramIdentifier $program_identifier): ProgramPrivacy
    {
        return $this->privacy;
    }
}
