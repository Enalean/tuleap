<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Factory;

use Mockery;
use Mockery\MockInterface;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_String;
use Tracker_FormElement_Field_Text;

class ChangesetBuilder
{
    /** @var Tracker_Artifact_Changeset|MockInterface */
    private $changeset;

    public function __construct()
    {
        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
    }

    public function submittedOn(int $timestamp): self
    {
        $this->changeset
            ->shouldReceive('getSubmittedOn')
            ->andReturn($timestamp)
            ->byDefault();
        return $this;
    }

    public function textValue(Tracker_FormElement_Field_Text $field, string $value): self
    {
        $this->changeset
            ->shouldReceive('getValue')
            ->with($field)
            ->andReturn(
                new class ($value) extends Tracker_Artifact_ChangesetValue_String
                {
                    /** @var string */
                    private $value;

                    public function __construct(string $value)
                    {
                        $this->value = $value;
                    }

                    public function getValue()
                    {
                        return $this->value;
                    }
                }
            );
        return $this;
    }

    public function firstListValue(MockInterface $field, string $value): self
    {
        $field->shouldReceive('getFirstValueFor')
            ->with($this->changeset)
            ->andReturn($value)
            ->byDefault();
        return $this;
    }

    /**
     * @return MockInterface|Tracker_Artifact_Changeset
     */
    public function build()
    {
        return $this->changeset;
    }
}
