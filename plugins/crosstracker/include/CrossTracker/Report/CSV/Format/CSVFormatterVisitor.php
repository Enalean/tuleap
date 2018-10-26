<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV\Format;

use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;

class CSVFormatterVisitor implements FormatterVisitor
{
    private $date_formatter;

    public function __construct(CSVFormatter $date_formatter)
    {
        $this->date_formatter = $date_formatter;
    }

    public function visitDateValue(DateValue $date_value, FormatterParameters $parameters)
    {
        return $this->date_formatter->formatDateForCSVForUser(
            $parameters->getUser(),
            $date_value->getValue(),
            $date_value->isTimeShown()
        );
    }

    /**
     * @return string
     */
    public function visitTextValue(TextValue $text_value, FormatterParameters $parameters)
    {
        $value = $text_value->getValue();
        $escaped_value = str_ireplace('"', '""', $value);
        return '"' . $escaped_value . '"';
    }

    public function visitUserValue(UserValue $user_value, FormatterParameters $parameters)
    {
        $user = $user_value->getValue();
        if ($user === null) {
            return '';
        }
        return $user->getUserName();
    }
}
