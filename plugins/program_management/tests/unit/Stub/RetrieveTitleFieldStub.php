<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveTitleField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

final class RetrieveTitleFieldStub implements RetrieveTitleField
{
    private function __construct(private TitleFieldReference $title, private bool $has_error)
    {
    }

    public static function withField(TitleFieldReference $title): self
    {
        return new self($title, false);
    }

    public static function withError(): self
    {
        return new self(TitleFieldReferenceStub::withDefaults(), true);
    }

    public function getTitleField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): TitleFieldReference
    {
        if ($this->has_error) {
            throw new FieldRetrievalException($tracker_identifier->getId(), "title");
        }

        return $this->title;
    }
}
