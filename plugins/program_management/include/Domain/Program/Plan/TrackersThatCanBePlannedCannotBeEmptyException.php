<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

final class TrackersThatCanBePlannedCannotBeEmptyException extends \Exception implements PlanTrackerException
{
    private string $i18n_message;

    public function __construct()
    {
        parent::__construct('Plannable tracker id cannot be empty');
        $this->i18n_message = dgettext('tuleap-program_management', 'Plannable tracker id cannot be empty');
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
