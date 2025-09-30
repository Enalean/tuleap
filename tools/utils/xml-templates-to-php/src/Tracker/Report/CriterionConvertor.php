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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tools\Xml2Php\Tracker\Report;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;

class CriterionConvertor
{
    public static function buildFromXML(
        \SimpleXMLElement $xml,
        IdToNameMapping $id_to_name_mapping,
    ): Expr {
        $criterion_expr = new New_(
            new Name('\\' . XMLReportCriterion::class),
            [
                new Arg(
                    new New_(
                        new Name('\\' . XMLReferenceByName::class),
                        [new Arg(new String_($id_to_name_mapping->get((string) $xml->field['REF'])))]
                    )
                ),
            ]
        );

        $criterion_expr = new MethodCall(
            $criterion_expr,
            'withRank',
            [new Arg(new LNumber((int) $xml['rank']))]
        );

        if ((string) $xml['is_advanced']) {
            $criterion_expr = new MethodCall($criterion_expr, 'withIsAdvanced');
        }

        if ($xml->criteria_value) {
            if ($xml->criteria_value->none_value) {
                $criterion_expr = new MethodCall($criterion_expr, 'withNoneSelected');
            }

            $selected_values = [];
            foreach ($xml->criteria_value->selected_value as $value) {
                $selected_values[] = new Arg(
                    new Expr\New_(
                        new Name('\\' . XMLBindValueReferenceById::class),
                        [
                            new Arg(new String_((string) $value['REF'])),
                        ]
                    )
                );
            }
            if ($selected_values) {
                $criterion_expr = new MethodCall($criterion_expr, 'withSelectedValues', $selected_values);
            }
        }

        return $criterion_expr;
    }
}
