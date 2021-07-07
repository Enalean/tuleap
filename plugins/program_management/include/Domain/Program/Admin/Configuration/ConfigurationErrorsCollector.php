<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;


final class ConfigurationErrorsCollector
{
    private bool $should_collect_all_issues;
    /**
     * @var string[]
     */
    private array $error_list = [];

    public function __construct(bool $should_collect_all_issues)
    {
        $this->should_collect_all_issues = $should_collect_all_issues;
    }

    public function shouldCollectAllIssues(): bool
    {
        return $this->should_collect_all_issues;
    }

    public function addError(string $ui_error): void
    {
        $this->error_list[] = $ui_error;
    }

    public function hasError(): bool
    {
        return count($this->error_list) > 0;
    }

    public function getErrorMessages(): array
    {
        return $this->error_list;
    }
}
