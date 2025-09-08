<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

final class MissingTimeFrameFieldException extends \Exception implements FieldSynchronizationException
{
    private string $i18n_message;

    public function __construct(int $tracker_id, string $field_kind)
    {
        parent::__construct(
            "Expected tracker with id $tracker_id to have a $field_kind field associated to its TimeFrame semantic, but this field was not found"
        );

        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                'Expected tracker with id #%d to have a %s field associated to its TimeFrame semantic, but this field was not found'
            ),
            $tracker_id,
            $field_kind
        );
    }

    #[\Override]
    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
