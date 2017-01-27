<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

class DateFieldChecker implements InvalidFieldChecker
{
    /**
     * @var DateValueExtractor
     */
    private $value_extractor;

    /**
     * @var DateFormatValidator
     */
    private $validator;

    public function __construct(DateFormatValidator $validator, DateValueExtractor $value_extractor)
    {
        $this->validator       = $validator;
        $this->value_extractor = $value_extractor;
    }

    public function checkFieldIsValidForComparison(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $value = $this->value_extractor->extractValue($comparison->getValueWrapper(), $field);

        $this->validator->checkValueIsValid($comparison, $field, $value);
    }
}
