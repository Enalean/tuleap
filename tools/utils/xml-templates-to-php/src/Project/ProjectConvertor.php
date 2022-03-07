<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tools\Xml2Php\Project;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use Psr\Log\LoggerInterface;
use Tuleap\Dashboard\XML\XMLColumn;
use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Dashboard\XML\XMLLine;
use Tuleap\Project\Service\XML\XMLService;
use Tuleap\Project\XML\XMLProject;
use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

class ProjectConvertor
{
    private function __construct(private \SimpleXMLElement $xml_project, private Expr $current_expr)
    {
    }

    public static function buildFromXML(\SimpleXMLElement $xml_project): self
    {
        $project_instance = new New_(
            new Name('\\' . XMLProject::class),
            [
                new Arg(new String_((string) $xml_project['unix-name'])),
                new Arg(new String_((string) $xml_project['full-name'])),
                new Arg(new String_((string) $xml_project['description'])),
                new Arg(new String_((string) $xml_project['access'])),
            ]
        );

        return new self($xml_project, $project_instance);
    }

    /**
     * @return Stmt\Expression[]
     */
    public function get(LoggerInterface $logger): array
    {
        $project_declarations = [
            new Stmt\Expression(
                new Assign(
                    new Variable('project'),
                    $this
                        ->withLongDescription($logger)
                        ->withServices()
                        ->withUserGroups($logger)
                        ->withDashboards()
                        ->getExpr()
                ),
            ),
        ];

        return $project_declarations;
    }

    private function getExpr(): Expr
    {
        return $this->current_expr;
    }

    private function withServices(): self
    {
        if (count($this->xml_project->services) > 0 && count($this->xml_project->services->service) > 0) {
            foreach ($this->xml_project->services->service as $service) {
                $this->current_expr = new MethodCall(
                    $this->current_expr,
                    'withService',
                    [
                        new Arg(
                            new StaticCall(
                                new Name('\\' . XMLService::class),
                                (string) $service['enabled'] === '1' ? 'buildEnabled' : 'buildDisabled',
                                [new Arg(new String_((string) $service['shortname']))]
                            )
                        ),
                    ]
                );
            }
        }

        return $this;
    }

    private function withDashboards(): self
    {
        if (count($this->xml_project->dashboards) > 0) {
            foreach ($this->xml_project->dashboards->dashboard as $dashboard) {
                $dashboard_expr = new New_(
                    new Name('\\' . XMLDashboard::class),
                    [new Arg(new String_((string) $dashboard['name']))]
                );

                foreach ($dashboard->line as $line) {
                    $line_expr = $line['layout']
                        ? new StaticCall(
                            new Name('\\' . XMLLine::class),
                            'withLayout',
                            [new Arg(new String_((string) $line['layout']))]
                        )
                        : new StaticCall(
                            new Name('\\' . XMLLine::class),
                            'withDefaultLayout'
                        );

                    foreach ($line->column as $column) {
                        $column_expr = new New_(
                            new Name('\\' . XMLColumn::class),
                        );

                        foreach ($column->widget as $widget) {
                            $widget_expr = new New_(
                                new Name('\\' . XMLWidget::class),
                                [new Arg(new String_((string) $widget['name']))]
                            );

                            foreach ($widget->preference as $preference) {
                                $preference_expr = new New_(
                                    new Name('\\' . XMLPreference::class),
                                    [new Arg(new String_((string) $preference['name']))]
                                );

                                foreach ($preference->reference as $reference) {
                                    $reference_expr = new StaticCall(
                                        new Name('\\' . XMLPreferenceValue::class),
                                        'ref',
                                        [
                                            new Arg(new String_((string) $reference['name'])),
                                            new Arg(new String_((string) $reference['REF'])),
                                        ]
                                    );

                                    $preference_expr = new MethodCall($preference_expr, 'withValue', [new Arg($reference_expr)]);
                                }

                                foreach ($preference->value as $value) {
                                    $value_expr = new StaticCall(
                                        new Name('\\' . XMLPreferenceValue::class),
                                        'text',
                                        [
                                            new Arg(new String_((string) $value['name'])),
                                            new Arg(new String_((string) $value)),
                                        ]
                                    );

                                    $preference_expr = new MethodCall($preference_expr, 'withValue', [new Arg($value_expr)]);
                                }

                                $widget_expr = new MethodCall($widget_expr, 'withPreference', [new Arg($preference_expr)]);
                            }

                            $column_expr = new MethodCall($column_expr, 'withWidget', [new Arg($widget_expr)]);
                        }

                        $line_expr = new MethodCall($line_expr, 'withColumn', [new Arg($column_expr)]);
                    }

                    $dashboard_expr = new MethodCall($dashboard_expr, 'withLine', [new Arg($line_expr)]);
                }

                $this->current_expr = new MethodCall(
                    $this->current_expr,
                    'withDashboard',
                    [new Arg($dashboard_expr)]
                );
            }
        }

        return $this;
    }

    private function withLongDescription(LoggerInterface $logger): self
    {
        if (count($this->xml_project->{'long-description'}) > 0) {
            $logger->error('long-description is not implemented yet');
        }

        return $this;
    }

    private function withUserGroups(LoggerInterface $logger): self
    {
        if (count($this->xml_project->ugroups) > 0) {
            $logger->error('ugroups is not implemented yet');
        }

        return $this;
    }
}
