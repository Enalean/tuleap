<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub\Semantic;

use Tracker;
use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Semantic\Title\GetTitleSemantic;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

final class GetTitleSemanticStub implements GetTitleSemantic
{
    private function __construct(private readonly ?Tracker_FormElement_Field_Text $text_field)
    {
    }

    public function getByTracker(Tracker $tracker): TrackerSemanticTitle
    {
        return new TrackerSemanticTitle($tracker, $this->text_field);
    }

    public static function withoutTextField(): self
    {
        return new self(null);
    }

    public static function withTextField(Tracker_FormElement_Field_Text $text_field): self
    {
        return new self($text_field);
    }
}
