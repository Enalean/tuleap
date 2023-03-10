<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace TuleapDev\Rector;

use PhpParser\Node;

final class CallbackAndAttributeName extends CallbackAndAttribute
{
    public function __construct(string $callback, Node\Expr\ClassConstFetch|Node\Scalar\String_ $arg)
    {
        if ($arg instanceof Node\Scalar\String_) {
            $hook = $arg->value;
        } else {
            $hook = $callback;
        }
        parent::__construct(
            $callback,
            $hook,
            new Node\Attribute(
                new Node\Name('\\' . \Tuleap\Plugin\ListeningToEventName::class),
                [
                    new Node\Arg($arg),
                ]
            )
        );
    }
}
