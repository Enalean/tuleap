<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use ForgeConfig;
use Rule_Date;
use Tracker_Artifact_ChangesetValue;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Date\TimezoneWrapper;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\User\ProvideCurrentUser;

#[ConfigKeyCategory('Tracker')]
class DateFormatter
{
    #[FeatureFlagConfigKey('Display date field with submitter timezone in artifact view')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const string DISPLAY_DATE_WITH_SUBMITTER_TIMEZONE = 'display_date_with_submitter_timezone';

    public const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        protected DateField $field,
        private readonly ProvideCurrentUser $current_user_provider,
    ) {
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
        array $errors,
    ) {
        $formatted_value = '';

        if (isset($submitted_values[$this->field->getId()])) {
            $formatted_value = $submitted_values[$this->field->getId()];
        } else {
            if ($value != null) {
                $timestamp = $value->getTimestamp();
                $timezone  = $value->getChangeset()->getSubmitter()->getTimezone();
                if ($timezone === '') {
                    $timezone = TimezoneRetriever::getUserTimezone($this->current_user_provider->getCurrentUser());
                }
                $formatted_value = $timestamp ? $this->formatDate($timestamp, $timezone) : '';
            }
        }

        return $this->getDatePicker($formatted_value, $errors);
    }

    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ) {
        if (empty($value) || ! $value->getTimestamp()) {
            return $this->field->getNoValueLabel();
        }

        $value_timestamp = $value->getTimestamp();
        $timezone        = $value->getChangeset()->getSubmitter()->getTimezone();
        if ($timezone === '') {
            $timezone = TimezoneRetriever::getUserTimezone($this->current_user_provider->getCurrentUser());
        }
        $formatted_value = $value_timestamp ? $this->formatDateForDisplay($value_timestamp, $timezone) : '';

        return $formatted_value;
    }

    public function fetchSubmitValue(array $submitted_values, array $errors): string
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
                    sprintf(dgettext('tuleap-tracker', '%1$s is not a date.'), $this->field->getLabel())
                );
            }
        }

        return $is_valid;
    }

    public function fetchSubmitValueMasschange(): string
    {
        return $this->getDatePicker(
            $GLOBALS['Language']->getText('global', 'unchanged'),
            []
        );
    }

    public function getFieldDataForCSVPreview(array $date_explode)
    {
        return $date_explode[0] . '-' . $date_explode[1] . '-' . $date_explode[2];
    }

    /**
     * Format a timestamp into Y-m-d format
     *
     * @param ?non-empty-string $timezone
     */
    public function formatDate($timestamp, ?string $timezone): string
    {
        if ((int) ForgeConfig::getFeatureFlag(self::DISPLAY_DATE_WITH_SUBMITTER_TIMEZONE) === 0) {
            return format_date(self::DATE_FORMAT, (float) $timestamp, '');
        }

        return TimezoneWrapper::wrapTimezone(
            $timezone ?? TimezoneRetriever::getUserTimezone($this->current_user_provider->getCurrentUser()),
            fn() => format_date(self::DATE_FORMAT, (float) $timestamp, ''),
        );
    }

    /**
     * @param ?non-empty-string $timezone
     */
    public function formatDateForDisplay($timestamp, ?string $timezone): string
    {
        if ((int) ForgeConfig::getFeatureFlag(self::DISPLAY_DATE_WITH_SUBMITTER_TIMEZONE) === 0) {
            return format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), (float) $timestamp, '');
        }

        return TimezoneWrapper::wrapTimezone(
            $timezone ?? TimezoneRetriever::getUserTimezone($this->current_user_provider->getCurrentUser()),
            fn() => format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), (float) $timestamp, ''),
        );
    }

    protected function getDatePicker($value, array $errors): string
    {
        return $GLOBALS['HTML']->getBootstrapDatePicker(
            'tracker_admin_field_' . $this->field->getId(),
            'artifact[' . $this->field->getId() . ']',
            $value,
            [],
            $errors,
            false,
            'date-time-' . $this->field->getName(),
            $this->field->isRequired(),
        );
    }
}
