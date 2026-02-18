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

namespace Tuleap\Tracker\FormElement;

use DateTimeImmutable;
use PFUser;
use TrackerFactory;
use Tuleap\dao\AddHistory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\FieldUsedInTriggerFault;
use Tuleap\Tracker\FormElement\Field\RemoveField;

/**
 * In that context remove an element means to unuse it, the field is not delete.
 */
final readonly class TrackerFieldRemover
{
    public function __construct(
        private RemoveField $form_element_factory,
        private TrackerFactory $tracker_factory,
        private AddHistory $project_history_dao,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function remove(TrackerFormElement $field, PFUser $current_user): Ok|Err
    {
        if (! $field->isUsed()) {
            return Result::ok(null);
        }

        $is_used_in_trigger = $this->tracker_factory->getTriggerRulesManager()->isUsedInTrigger($field);
        if ($is_used_in_trigger) {
            return Result::err(FieldUsedInTriggerFault::build());
        }
        $this->form_element_factory->removeFormElement($field->id);

        $project              = $field->getTracker()->getProject();
        $project_history_enum = TrackerFormElementHistoryEntry::build(TrackerFormElementHistoryEntry::Unused->value);

        $this->project_history_dao->addHistory(
            $project,
            $current_user,
            new DateTimeImmutable(),
            $project_history_enum->getLabel(),
            $project_history_enum->getValue($field),
        );
        return Result::ok(null);
    }
}
