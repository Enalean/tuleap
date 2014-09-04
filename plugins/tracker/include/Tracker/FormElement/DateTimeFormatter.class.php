<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_FormElement_DateTimeFormatter extends Tracker_FormElement_DateFormatter {
    const DATE_TIME_FORMAT           = 'Y-m-d h:i:s';

    public function __construct(Tracker_FormElement_Field_Date $field) {
        parent::__construct($field);
    }

    public function getFormat() {
        return self::DATE_TIME_FORMAT;
    }

    public function validate($value) {
        $is_valid = true;
        if ($value) {
            $rule     = new Rule_Date_Time();
            $is_valid = $rule->isValid($value);
            if (! $is_valid) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText(
                        'plugin_tracker_common_artifact',
                        'error_date_value', array($this->field->getLabel())
                    )
                );
            }
        }

        return $is_valid;
    }

    public function formatDate($timestamp) {
        return format_date(self::DATE_TIME_FORMAT, (float)$timestamp, '');
    }

    protected function getDatePicker($value, array $errors) {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            "tracker_admin_field_". $this->field->getId(),
            'artifact['. $this->field->getId() .']',
            $value,
            array(),
            $errors,
            true
        );
    }
}
