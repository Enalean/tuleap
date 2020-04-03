<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Rule;

use Feedback;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tracker_Rule_Date;

class TrackerRulesDateValidator
{
    /**
     * Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function validateDateRules(array $value_field_list, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (! $this->dateRuleApplyToSubmittedFields($rule, $value_field_list)) {
                return false;
            }

            if (! $this->validateDateRuleOnSubmittedFields($rule, $value_field_list)) {
                $source_field = $this->getFieldById($rule->getSourceFieldId());
                $target_field = $this->getFieldById($rule->getTargetFieldId());
                $feedback = $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact',
                    'rules_date_not_valid',
                    [
                        $source_field->getLabel() ,
                        $rule->getComparator(),
                        $target_field->getLabel()
                    ]
                );

                $GLOBALS['Response']->addFeedback('error', $feedback);

                $source_field->setHasErrors(true);
                $target_field->setHasErrors(true);
                return false;
            }
        }

        return true;
    }

    private function dateRuleApplyToSubmittedFields(Tracker_Rule_Date $rule, array $value_field_list): bool
    {
        $is_valid = true;
        if (! array_key_exists($rule->getSourceFieldId(), $value_field_list)) {
            $source_field = $this->getFieldById($rule->getSourceFieldId());
            $feedback = $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_field_value') . $source_field->getLabel();

            $GLOBALS['Response']->addUniqueFeedback(Feedback::ERROR, $feedback);
            $source_field->setHasErrors(true);
            $is_valid = false;
        }

        if (! array_key_exists($rule->getTargetFieldId(), $value_field_list)) {
            $target_field = $this->getFieldById($rule->getTargetFieldId());
            $feedback = $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_field_value') . $target_field->getLabel();
            $GLOBALS['Response']->addUniqueFeedback(Feedback::ERROR, $feedback);
            $target_field->setHasErrors(true);
            $is_valid = false;
        }

        return $is_valid;
    }

    private function validateDateRuleOnSubmittedFields(Tracker_Rule_Date $rule, array $value_field_list): bool
    {
        $source_value = $value_field_list[$rule->getSourceFieldId()];
        $target_value = $value_field_list[$rule->getTargetFieldId()];

        return $rule->validate($source_value, $target_value);
    }

    private function getFieldById($field_id): Tracker_FormElement_Field
    {
        return $this->form_element_factory->getFormElementById($field_id);
    }
}
