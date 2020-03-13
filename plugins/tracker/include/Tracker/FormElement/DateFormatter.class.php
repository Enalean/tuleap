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

class Tracker_FormElement_DateFormatter
{
    public const DATE_FORMAT           = "Y-m-d";

    /** @var Tracker_FormElement_Field_Date */
    protected $field;

    public function __construct(Tracker_FormElement_Field_Date $field)
    {
        $this->field = $field;
    }

    public function getFormat()
    {
        return self::DATE_FORMAT;
    }

    /**
     * @return string
     */
    public function fetchArtifactValue(
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
        array $errors
    ) {
        $formatted_value = '';

        if (isset($submitted_values[$this->field->getId()])) {
            $formatted_value = $submitted_values[$this->field->getId()];
        } else {
            if ($value != null) {
                $timestamp       = $value->getTimestamp();
                $formatted_value = $timestamp ? $this->formatDate($timestamp) : '';
            }
        }

        return $this->getDatePicker($formatted_value, $errors);
    }

    public function fetchArtifactValueReadOnly(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null
    ) {
        if (empty($value) || ! $value->getTimestamp()) {
            return $this->field->getNoValueLabel();
        }

        $value_timestamp = $value->getTimestamp();
        $formatted_value = $value_timestamp ? $this->formatDateForDisplay($value_timestamp) : '';

        return $formatted_value;
    }

    public function fetchSubmitValue(array $submitted_values, array $errors)
    {
        $value = $this->field->getValueFromSubmitOrDefault($submitted_values);

        return $this->getDatePicker($value, $errors);
    }

    public function validate($value)
    {
        $is_valid = true;
        if ($value) {
            $rule     = new Rule_Date();
            $is_valid = $rule->isValid($value);
            if (! $is_valid) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText(
                        'plugin_tracker_common_artifact',
                        'error_date_value',
                        array($this->field->getLabel())
                    )
                );
            }
        }

        return $is_valid;
    }

    public function fetchSubmitValueMasschange()
    {
        return $this->getDatePicker(
            $GLOBALS['Language']->getText('global', 'unchanged'),
            array()
        );
    }

    public function getFieldDataForCSVPreview(array $date_explode)
    {
        return $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2];
    }

    /**
     * Format a timestamp into Y-m-d format
     */
    public function formatDate($timestamp)
    {
        return format_date(self::DATE_FORMAT, (float) $timestamp, '');
    }

    public function formatDateForDisplay($timestamp)
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), (float) $timestamp, '');
    }

    protected function getDatePicker($value, array $errors)
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            "tracker_admin_field_" . $this->field->getId(),
            'artifact[' . $this->field->getId() . ']',
            $value,
            array(),
            $errors,
            false
        );
    }
}
