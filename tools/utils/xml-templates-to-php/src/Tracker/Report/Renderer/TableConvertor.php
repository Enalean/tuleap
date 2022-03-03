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
use Tuleap\Tools\Xml2Php\Tracker\FormElement\IdToNameMapping;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;
use Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable;

final class TableConvertor extends RendererConvertor
{
    public function buildFromXml(
        \SimpleXMLElement $renderer,
        LoggerInterface $logger,
        IdToNameMapping $id_to_name_mapping,
    ): Expr {
        $expr = new New_(
            new Name('\\' . XMLTable::class),
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

        if ((int) $renderer['chunksz']) {
            $expr = new MethodCall(
                $expr,
                'withChunkSize',
                [new Arg(new LNumber((int) $renderer['chunksz']))]
            );
        }

        if ($renderer->columns) {
            $columns = [];
            foreach ($renderer->columns->field as $field) {
                $columns[] = new Arg(
                    new New_(
                        new Name('\\' . XMLTableColumn::class),
                        [
                            new Arg(
                                new New_(
                                    new Name('\\' . XMLReferenceByName::class),
                                    [new Arg(new String_($id_to_name_mapping->get((string) $field['REF'])))]
                                )
                            ),
                        ]
                    )
                );
            }
            if ($columns) {
                $expr = new MethodCall($expr, 'withColumns', $columns);
            }
        }

        return $expr;
    }
}
