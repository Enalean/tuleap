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

class Tracker_FormElement_DateTimeFormatter extends Tracker_FormElement_DateFormatter
{
    public const DATE_TIME_FORMAT = 'Y-m-d H:i';

    public function __construct(Tracker_FormElement_Field_Date $field)
    {
        parent::__construct($field);
    }

    public function validate($value)
    {
        $is_valid = true;
        if ($value) {
            $rule     = new Rule_Date_Time();
            $is_valid = $rule->isValid($value);
            if (! $is_valid) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText(
                        'plugin_tracker_common_artifact',
                        'error_datetime_value',
                        array($this->field->getLabel(), $this->getUserDateFormatPreference())
                    )
                );
            }
        }

        return $is_valid;
    }

    private function getUserDateFormatPreference()
    {
        $user_preference = UserManager::instance()->getCurrentUser()->getPreference('user_csv_dateformat');
        $format          = '';

        switch ($user_preference) {
            case 'month_day_year':
                $format = 'mm/dd/yyyy';
                break;
            case 'day_month_year':
                $format = 'dd/mm/yyyy';
                break;
            default:
                $format = 'dd/mm/yyyy';
                break;
        }

        return $format;
    }

    public function getFieldDataForCSVPreview(array $date_explode)
    {
        return $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2]
            . ' ' . $date_explode[3] . ':' . $date_explode[4];
    }

    public function formatDate($timestamp)
    {
        return format_date(self::DATE_TIME_FORMAT, (float) $timestamp, '');
    }

    public function formatDateForDisplay($timestamp)
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt_time'), (float) $timestamp, '');
    }

    protected function getDatePicker($value, array $errors)
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            "tracker_admin_field_" . $this->field->getId(),
            'artifact[' . $this->field->getId() . ']',
            $value,
            array(),
            $errors,
            true
        );
    }
}
