<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use Tuleap\NeverThrow\Fault;

/**
 * @psalm-immutable
 */
final readonly class InvalidXMLContentFault extends Fault
{
    public static function fromLibXMLErrors(array $errors): Fault
    {
        return new self(
            implode(
                PHP_EOL,
                [
                    _('XML content is not valid'),
                    ...array_map(
                        static fn (\LibXMLError $xml_error) => "Line: $xml_error->line \t Column: $xml_error->column \t Message: " . trim($xml_error->message),
                        $errors,
                    ),
                ]
            )
        );
    }

    public static function fromEmptyContent(): Fault
    {
        return new self(_('XML content is empty'));
    }
}
