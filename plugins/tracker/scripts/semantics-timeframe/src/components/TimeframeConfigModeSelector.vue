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
    <div class="tlp-form-element">
        <label class="tlp-label" for="imeframe-mode">
            {{ $gettext("The timeframe of an artifact will be") }}
        </label>
        <select
            id="timeframe-mode"
            name="timeframe-mode"
            class="tlp-form-element tlp-select tlp-select-adjusted"
            v-on:change="dispatchSelection"
            v-model="active_timeframe_mode"
            data-test="timeframe-mode-select-box"
            required
        >
            <option value="" disabled>{{ $gettext("Choose a method...") }}</option>
            <option
                v-for="mode in timeframe_modes"
                v-bind:value="mode.id"
                v-bind:key="mode.id"
                v-bind:disabled="mode.disabled"
                v-bind:title="mode.title"
                v-bind:data-test="'timeframe-mode-' + mode.id"
            >
                {{ mode.name }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { MODE_BASED_ON_TRACKER_FIELDS, MODE_IMPLIED_FROM_ANOTHER_TRACKER } from "../constants";
import type { TimeframeMode } from "../type";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    implied_from_tracker_id: number | "";
    should_send_event_in_notification: boolean;
    has_other_trackers_implying_their_timeframes: boolean;
}>();

const emit = defineEmits(["timeframe-mode-selected"]);

const active_timeframe_mode = ref<
    typeof MODE_IMPLIED_FROM_ANOTHER_TRACKER | typeof MODE_BASED_ON_TRACKER_FIELDS | ""
>("");

onMounted((): void => {
    active_timeframe_mode.value =
        props.implied_from_tracker_id !== ""
            ? MODE_IMPLIED_FROM_ANOTHER_TRACKER
            : MODE_BASED_ON_TRACKER_FIELDS;

    dispatchSelection();
});

function dispatchSelection(): void {
    emit("timeframe-mode-selected", active_timeframe_mode.value);
}

const gettext_provider = useGettext();

const title_error_for_implied_option = computed((): string => {
    if (props.should_send_event_in_notification) {
        return gettext_provider.$gettext(
            "The semantic cannot be inherited from another tracker because calendar events are activated in notification",
        );
    }

    if (props.has_other_trackers_implying_their_timeframes) {
        return gettext_provider.$gettext(
            "You cannot make this semantic inherit from another tracker because some other trackers are inheriting their own semantics timeframe from this one.",
        );
    }

    return "";
});

const disabled_for_implied_option = computed((): boolean => {
    return title_error_for_implied_option.value !== "";
});

const timeframe_modes = computed((): TimeframeMode[] => {
    return [
        {
            id: MODE_BASED_ON_TRACKER_FIELDS,
            name: gettext_provider.$gettext("Based on tracker fields"),
            disabled: false,
            title: "",
        },
        {
            id: MODE_IMPLIED_FROM_ANOTHER_TRACKER,
            name: gettext_provider.$gettext("Inherited from another tracker"),
            disabled: disabled_for_implied_option.value,
            title: title_error_for_implied_option.value,
        },
    ];
});
</script>
