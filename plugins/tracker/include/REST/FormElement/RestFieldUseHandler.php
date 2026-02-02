<?php
/**
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Tracker\FormElement\TrackerFieldAdder;
use Tuleap\Tracker\FormElement\TrackerFieldRemover;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final readonly class RestFieldUseHandler
{
    public function __construct(private TrackerFieldRemover $field_remover, private TrackerFieldAdder $field_adder)
    {
    }

    /**
     * @throws \Luracast\Restler\RestException
     */
    public function handle(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch): void
    {
        if ($patch->use_it === null) {
            return;
        }

        if ($patch->use_it === true) {
            $this->field_adder->add($field);
        }

        if ($patch->use_it === false) {
            $this->field_remover->remove($field)->mapErr(
                static fn(Fault $fault) => throw new RestException(
                    400,
                    (string) $fault
                )
            );
        }
    }
}
