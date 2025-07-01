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

namespace Tuleap\Tools\Xml2Php\Tracker;

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
use Tuleap\Color\ItemColor;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\FormElementConvertorBuilder;
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tools\Xml2Php\Tracker\Report\ReportConvertor;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Workflow\XML\XMLSimpleWorkflow;
use Tuleap\Tracker\XML\XMLTracker;

class TrackerConvertor
{
    private Expr $current_expr;
    private \SimpleXMLElement $xml_tracker;

    private function __construct(\SimpleXMLElement $xml_tracker, Expr $current_expr)
    {
        $this->current_expr = $current_expr;
        $this->xml_tracker  = $xml_tracker;
    }

    public static function buildFromXML(\SimpleXMLElement $xml_tracker): self
    {
        $tracker_instance = new New_(
            new Name('\\' . XMLTracker::class),
            [
                new Arg(new String_((string) $xml_tracker['id'])),
                new Arg(new String_((string) $xml_tracker->item_name)),
            ]
        );

        return new self($xml_tracker, $tracker_instance);
    }

    /**
     * @return Stmt\Expression[]
     */
    public function get(LoggerInterface $output): array
    {
        $tracker_declaration = [
            new Stmt\Expression(
                new Assign(
                    new Variable('tracker'),
                    $this
                        ->withName()
                        ->withColor()
                        ->withDescription()
                        ->withSubmitInstructions()
                        ->withBrowseInstructions()
                        ->getExpr()
                ),
            ),
        ];

        $id_to_name_mapping = new IdToNameMapping();

        $tracker                 = new Variable('tracker');
        $this->current_expr      = $tracker;
        $additional_declarations = $this
            ->withFormElements($output, $id_to_name_mapping)
            ->withSemantics($output, $id_to_name_mapping)
            ->withReports($output, $id_to_name_mapping)
            ->withWorkflow($output, $id_to_name_mapping)
            ->getExpr();

        if ($this->current_expr !== $tracker) {
            $tracker_declaration[] = new Stmt\Expression(
                new Assign(
                    new Variable('my_awesome_tracker'),
                    $additional_declarations
                )
            );
        }

        return $tracker_declaration;
    }

    private function getExpr(): Expr
    {
        return $this->current_expr;
    }

    private function withName(): self
    {
        if ((string) $this->xml_tracker->name) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withName',
                [new Arg(new String_((string) $this->xml_tracker->name))]
            );
        }

        return $this;
    }

    private function withColor(): self
    {
        if ((string) $this->xml_tracker->color) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withColor',
                [
                    new Arg(
                        new StaticCall(
                            new Name('\\' . ItemColor::class),
                            'fromName',
                            [new Arg(new String_((string) $this->xml_tracker->color))]
                        )
                    ),
                ]
            );
        }

        return $this;
    }

    private function withDescription(): self
    {
        if ((string) $this->xml_tracker->description) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withDescription',
                [new Arg(new String_((string) $this->xml_tracker->description))]
            );
        }

        return $this;
    }

    private function withSubmitInstructions(): self
    {
        if ((string) $this->xml_tracker->submit_instructions) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withSubmitInstructions',
                [new Arg(new String_((string) $this->xml_tracker->submit_instructions))]
            );
        }

        return $this;
    }

    private function withBrowseInstructions(): self
    {
        if ((string) $this->xml_tracker->browse_instructions) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withBrowseInstructions',
                [new Arg(new String_((string) $this->xml_tracker->browse_instructions))]
            );
        }

        return $this;
    }

    private function withFormElements(LoggerInterface $output, IdToNameMapping $id_to_name_mapping): self
    {
        if (! $this->xml_tracker->formElements) {
            return $this;
        }

        $form_elements_exprs = [];
        foreach ($this->xml_tracker->formElements->formElement as $form_element) {
            $convertor = FormElementConvertorBuilder::buildFromXML($form_element, $this->xml_tracker, $output);
            if ($convertor) {
                $form_elements_exprs[] = new Arg(
                    $convertor->get($output, $id_to_name_mapping)
                );
            }
        }

        if ($form_elements_exprs) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withFormElement',
                $form_elements_exprs
            );
        }

        return $this;
    }

    private function withSemantics(LoggerInterface $output, IdToNameMapping $id_to_name_mapping): self
    {
        if (! $this->xml_tracker->semantics) {
            return $this;
        }

        $semantics = [];
        foreach ($this->xml_tracker->semantics->semantic as $semantic) {
            $expr = SemanticConvertor::buildFromXML($semantic, $output, $id_to_name_mapping);
            if ($expr) {
                $semantics[] = new Arg($expr);
            }
        }

        if ($semantics) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withSemantics',
                $semantics
            );
        }

        return $this;
    }

    private function withReports(LoggerInterface $output, IdToNameMapping $id_to_name_mapping): self
    {
        if (! $this->xml_tracker->reports) {
            return $this;
        }

        $reports = [];
        foreach ($this->xml_tracker->reports->report as $report) {
            $reports[] = new Arg(
                ReportConvertor::buildFromXML($report, $output, $id_to_name_mapping)
            );
        }

        if ($reports) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withReports',
                $reports
            );
        }

        return $this;
    }

    public function withWorkflow(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        if ($this->xml_tracker->simple_workflow) {
            return $this->withSimpleWorkflow($this->xml_tracker->simple_workflow, $logger, $id_to_name_mapping);
        }

        if ($this->xml_tracker->workflow) {
            $logger->error('Workflow is not implemented yet');
        }

        return $this;
    }

    private function withSimpleWorkflow(
        \SimpleXMLElement $simple_workflow,
        LoggerInterface $logger,
        IdToNameMapping $id_to_name_mapping,
    ): self {
        $workflow = new New_(
            new Name('\\' . XMLSimpleWorkflow::class)
        );

        if ($simple_workflow->field_id) {
            $workflow = new MethodCall(
                $workflow,
                'withField',
                [
                    new Arg(
                        new New_(
                            new Name('\\' . XMLReferenceByName::class),
                            [new Arg(new String_($id_to_name_mapping->get((string) $simple_workflow->field_id['REF'])))]
                        )
                    ),
                ]
            );
        }

        if ((int) $simple_workflow->is_used) {
            $workflow = new MethodCall($workflow, 'withIsUsed');
        }

        $logger->error('Workflow states are not implemented yet');

        $this->current_expr = new MethodCall($this->current_expr, 'withWorkflow', [new Arg($workflow)]);

        return $this;
    }
}
