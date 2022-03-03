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

namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\LNumber;
use Psr\Log\LoggerInterface;

class TextConvertor extends FieldConvertor
{
    protected function buildWith(LoggerInterface $logger, IdToNameMapping $id_to_name_mapping): self
    {
        parent::buildWith($logger, $id_to_name_mapping);

        return $this
            ->withRows()
            ->withCols();
    }

    private function withRows(): self
    {
        $properties = $this->xml->properties;
        if (! $properties) {
            return $this;
        }

        if ((int) $properties['rows']) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withRows',
                [new Arg(new LNumber((int) $properties['rows']))]
            );
        }

        return $this;
    }

    private function withCols(): self
    {
        $properties = $this->xml->properties;
        if (! $properties) {
            return $this;
        }

        if ((int) $properties['cols']) {
            $this->current_expr = new MethodCall(
                $this->current_expr,
                'withCols',
                [new Arg(new LNumber((int) $properties['cols']))]
            );
        }

        return $this;
    }
}
