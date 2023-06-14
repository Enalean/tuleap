<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Tracker\XML\Updater\UpdateBindValueForSemantic;

final class UpdateBindValueForSemanticStub implements UpdateBindValueForSemantic
{
    private function __construct(private int $nb_calls)
    {
    }

    public static function build(): self
    {
        return new self(0);
    }

    public function getNbValuesUpdated(): int
    {
        return $this->nb_calls;
    }

    public function updateValueForSemanticMove(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field_List $source_status_field,
        Tracker_FormElement_Field_List $target_status_field,
        int $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $this->nb_calls++;
    }
}
