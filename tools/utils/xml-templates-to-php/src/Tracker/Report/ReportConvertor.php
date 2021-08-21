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
use PhpParser\Node\Scalar\String_;
use Psr\Log\LoggerInterface;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tools\Xml2Php\Tracker\Report\Renderer\RendererConvertorBuilder;
use Tuleap\Tracker\Report\XML\XMLReport;

class ReportConvertor
{
    public static function buildFromXML(
        \SimpleXMLElement $xml,
        LoggerInterface $logger,
        IdToNameMapping $id_to_name_mapping,
    ): Expr {
        $report_expr = new New_(
            new Name('\\' . XMLReport::class),
            [new Arg(new String_((string) $xml->name))]
        );

        if ((string) $xml['is_default'] === '1') {
            $report_expr = new MethodCall(
                $report_expr,
                'withIsDefault',
                [new Arg(new Expr\ConstFetch(new Name('true')))]
            );
        }

        if ((string) $xml['is_in_expert_mode'] === '1') {
            $report_expr = new MethodCall($report_expr, 'withExpertMode');
        }

        if ((string) $xml['expert_query']) {
            $report_expr = new MethodCall(
                $report_expr,
                'withExpertQuery',
                [new Arg(new String_((string) $xml['expert_query']))]
            );
        }

        if ((string) $xml->description) {
            $report_expr = new MethodCall(
                $report_expr,
                'withDescription',
                [new Arg(new String_((string) $xml->description))]
            );
        }


        $criteria = [];
        foreach ($xml->criterias->criteria as $criterion) {
            $criteria[] = new Arg(
                CriterionConvertor::buildFromXML($criterion, $id_to_name_mapping)
            );
        }

        if ($criteria) {
            $report_expr = new MethodCall($report_expr, 'withCriteria', $criteria);
        }

        $renderers = [];
        foreach ($xml->renderers->renderer as $renderer) {
            $convertor = RendererConvertorBuilder::getConvertor($renderer, $logger);
            if ($convertor) {
                $renderers[] = new Arg(
                    $convertor->buildFromXml($renderer, $logger, $id_to_name_mapping)
                );
            }
        }
        if ($renderers) {
            $report_expr = new MethodCall($report_expr, 'withRenderers', $renderers);
        }

        return $report_expr;
    }
}
