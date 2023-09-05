<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-pane-section-submit">
        <button class="tlp-button-primary" type="submit" name="update-semantic-timeframe">
            {{ $gettext("Save your modifications") }}
        </button>

        <template v-if="is_semantic_configured">
            <button
                class="tlp-button-danger tlp-button-outline reset-semantic-timeframe"
                type="submit"
                name="reset-semantic-timeframe"
                id="reset-semantic-timeframe"
                data-test="reset-button"
                v-bind:title="cannot_reset_message"
                v-bind:disabled="is_reset_disabled"
            >
                {{ $gettext("Reset this semantic") }}
            </button>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    start_date_field_id: number | "";
    end_date_field_id: number | "";
    duration_field_id: number | "";
    has_other_trackers_implying_their_timeframes: boolean;
    has_tracker_charts: boolean;
    implied_from_tracker_id: number | "";
}>();

const gettext_provider = useGettext();

const is_semantic_configured = computed((): boolean => {
    return (
        (props.start_date_field_id !== "" && props.end_date_field_id !== "") ||
        (props.start_date_field_id !== "" && props.duration_field_id !== "") ||
        props.implied_from_tracker_id !== ""
    );
});

const is_reset_disabled = computed((): boolean => {
    return props.has_other_trackers_implying_their_timeframes || props.has_tracker_charts;
});

const cannot_reset_message = computed((): string => {
    if (props.has_other_trackers_implying_their_timeframes) {
        return gettext_provider.$gettext(
            "You cannot reset this semantic because some trackers inherit their own semantics timeframe from this one.",
        );
    }

    if (props.has_tracker_charts) {
        return gettext_provider.$gettext(
            "You cannot reset this semantic because this tracker has a burnup, burndown or another chart rendered by an external plugin",
        );
    }

    return "";
});
</script>
