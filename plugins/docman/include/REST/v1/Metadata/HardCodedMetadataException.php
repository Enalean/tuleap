<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Metadata;

use Exception;

class HardCodedMetadataException extends Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message)
    {
        parent::__construct($message);
        $this->i18n_message = $i18n_message;
    }

    public static function invalidDateComparison(): self
    {
        return new self(
            "obsolescence date before today",
            dgettext('tuleap-docman', 'The obsolescence date is before the current date')
        );
    }

    public static function invalidDateFormat(): self
    {
        return new self(
            "obsolescence date format is incorrect",
            dgettext('tuleap-docman', 'The date format is incorrect. The format must be "YYYY-MM-DD"')
        );
    }

    public static function obsolescenceDateMetadataIsDisabled(): self
    {
        return new self(
            "obsolescence date is not enabled for project",
            dgettext('tuleap-docman', 'The project does not support obsolescence date, you should not provide it to create or update a new document.')
        );
    }

    public static function itemStatusNotAvailable(): self
    {
        return new self(
            "Status is not enabled for project",
            dgettext('tuleap-docman', 'The "Status" property is not activated for this item.')
        );
    }

    public static function itemStatusIsInvalid(string $status): self
    {
        return new self(
            sprintf("Status %s is invalid", $status),
            sprintf(dgettext('tuleap-docman', 'The status "%s" is invalid.'), $status)
        );
    }

    public static function itemStatusNullIsInvalid(): self
    {
        return new self(
            "Status null is invalid",
            dgettext('tuleap-docman', 'null is not a valid status.')
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
