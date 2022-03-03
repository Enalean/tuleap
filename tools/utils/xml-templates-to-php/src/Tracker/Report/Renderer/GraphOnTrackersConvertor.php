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

namespace Tuleap\Tools\Xml2Php\Tracker\Report\Renderer;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\GraphOnTrackersV5\XML\XMLBarChart;
use Tuleap\GraphOnTrackersV5\XML\XMLGraphOnTrackerRenderer;
use Tuleap\GraphOnTrackersV5\XML\XMLPieChart;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;

final class GraphOnTrackersConvertor extends RendererConvertor
{
    public function buildFromXml(
        SimpleXMLElement $renderer,
        LoggerInterface $logger,
        IdToNameMapping $id_to_name_mapping,
    ): Expr {
        $expr = new New_(
            new Name('\\' . XMLGraphOnTrackerRenderer::class),
            [new Arg(new String_((string) $renderer->name))]
        );

        if ((string) $renderer['ID']) {
            $expr = new MethodCall(
                $expr,
                'withId',
                [new Arg(new String_((string) $renderer['ID']))]
            );
        }

        if ((string) $renderer->description) {
            $expr = new MethodCall(
                $expr,
                'withDescription',
                [new Arg(new String_((string) $renderer->description))]
            );
        }

        $charts = [];
        if ($renderer->charts) {
            foreach ($renderer->charts->chart as $chart) {
                $chart_type = (string) $chart['type'];
                switch ($chart_type) {
                    case 'pie':
                        $chart_expr = new New_(
                            new Name('\\' . XMLPieChart::class),
                            [
                                new Arg(new LNumber((int) $chart['width'])),
                                new Arg(new LNumber((int) $chart['height'])),
                                new Arg(new LNumber((int) $chart['rank'])),
                                new Arg(new String_((string) $chart->title)),
                            ]
                        );

                        if ((string) $chart->description) {
                            $chart_expr = new MethodCall(
                                $chart_expr,
                                'withDescription',
                                [new Arg(new String_((string) $chart->description))]
                            );
                        }

                        if ((string) $chart['base']) {
                            $chart_expr = new MethodCall(
                                $chart_expr,
                                'withBase',
                                [
                                    new Arg(
                                        new New_(
                                            new Name('\\' . XMLReferenceByName::class),
                                            [new Arg(new String_($id_to_name_mapping->get((string) $chart['base'])))]
                                        )
                                    ),
                                ]
                            );
                        }

                        $charts[] = new Arg($chart_expr);
                        break;
                    case 'bar':
                        $chart_expr = new New_(
                            new Name('\\' . XMLBarChart::class),
                            [
                                new Arg(new LNumber((int) $chart['width'])),
                                new Arg(new LNumber((int) $chart['height'])),
                                new Arg(new LNumber((int) $chart['rank'])),
                                new Arg(new String_((string) $chart->title)),
                            ]
                        );

                        if ((string) $chart->description) {
                            $chart_expr = new MethodCall(
                                $chart_expr,
                                'withDescription',
                                [new Arg(new String_((string) $chart->description))]
                            );
                        }

                        if ((string) $chart['base']) {
                            $chart_expr = new MethodCall(
                                $chart_expr,
                                'withBase',
                                [
                                    new Arg(
                                        new New_(
                                            new Name('\\' . XMLReferenceByName::class),
                                            [new Arg(new String_($id_to_name_mapping->get((string) $chart['base'])))]
                                        )
                                    ),
                                ]
                            );
                        }

                        if ((string) $chart['group']) {
                            $chart_expr = new MethodCall(
                                $chart_expr,
                                'withGroup',
                                [
                                    new Arg(
                                        new New_(
                                            new Name('\\' . XMLReferenceByName::class),
                                            [new Arg(new String_($id_to_name_mapping->get((string) $chart['group'])))]
                                        )
                                    ),
                                ]
                            );
                        }

                        $charts[] = new Arg($chart_expr);
                        break;
                    default:
                        $logger->error(sprintf('%s charts are not implemented yet', $chart_type));
                }
            }
        }

        if ($charts) {
            $expr = new MethodCall($expr, 'withCharts', $charts);
        }

        return $expr;
    }
}
