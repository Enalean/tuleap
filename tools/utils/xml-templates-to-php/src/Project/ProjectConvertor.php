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
use Tuleap\Project\Service\XML\XMLService;
use Tuleap\Project\XML\XMLProject;

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
