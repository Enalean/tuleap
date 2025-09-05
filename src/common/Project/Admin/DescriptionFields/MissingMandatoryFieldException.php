<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\Project\Registration\RegistrationErrorException;

final class MissingMandatoryFieldException extends \RuntimeException implements RegistrationErrorException
{
    private array $mandatory_fields;

    public function __construct(array $mandatory_fields)
    {
        $this->mandatory_fields = $mandatory_fields;

        parent::__construct(
            sprintf(
                'Mandatory field where missing: %s',
                implode(
                    ', ',
                    array_values($mandatory_fields)
                )
            )
        );
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(
            dgettext('tuleap-core', 'Mandatory field where missing: %s'),
            implode(
                ', ',
                array_values($this->mandatory_fields)
            )
        );
    }
}
