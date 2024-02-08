<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <tr>
        <td class="tlp-form-element">
            <span class="tlp-prepend"></span>
            <input
                type="text"
                class="tlp-input tlp-input-date"
                data-test="timetracking-date"
                v-model="date"
                ref="date_field"
                size="11"
            />
        </td>
        <td>
            <input
                type="text"
                class="tlp-input"
                id="timetracking-details-modal-add-step-field"
                size="11"
                placeholder="preparation"
                v-on:keyup.enter="validateNewTime()"
                v-model="step"
            />
        </td>
        <td class="timetracking-details-modal-buttons">
            <div
                class="tlp-form-element timetracking-details-form-element"
                v-bind:class="{ 'tlp-form-element-error': error_message }"
            >
                <input
                    type="text"
                    class="tlp-input"
                    size="11"
                    v-model="time"
                    data-test="timetracking-time"
                    v-on:keyup.enter="validateNewTime()"
                    placeholder="hh:mm"
                    required
                />
            </div>
            <button
                class="tlp-button-primary"
                type="submit"
                data-test="timetracking-submit-time"
                v-bind:disabled="is_loading"
                v-bind:class="{
                    'tlp-tooltip tlp-tooltip-bottom timetracking-tooltip': error_message,
                }"
                v-bind:data-tlp-tooltip="error_message"
                v-on:click="validateNewTime()"
            >
                <i v-bind:class="getButtonIconClass"></i>
            </button>
            <button
                class="tlp-button-primary tlp-button-outline"
                type="button"
                v-on:click="swapMode()"
            >
                <i class="fa fa-times"></i>
            </button>
        </td>
    </tr>
</template>
<script setup lang="ts">
import {
    formatMinutes,
    formatDatetimeToYearMonthDay,
} from "@tuleap/plugin-timetracking-time-formatters";
import { TIME_REGEX } from "@tuleap/plugin-timetracking-constants";
import { datePicker } from "tlp";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const props = defineProps<{
    timeData: PersonalTime | undefined;
    artifact: Artifact;
}>();

const date = ref(
    props.timeData
        ? formatDatetimeToYearMonthDay(props.timeData.date)
        : formatDatetimeToYearMonthDay(new Date().toISOString()),
);
const step = ref(props.timeData && props.timeData.step ? props.timeData.step : "");
const time = ref(
    props.timeData && props.timeData.minutes ? formatMinutes(props.timeData.minutes) : "",
);
const error_message = ref<string>("");
const is_loading = ref(false);
const date_field = ref<HTMLInputElement>();

const getButtonIconClass = computed((): string => {
    if (is_loading.value) {
        return "fa fa-spinner";
    }
    return "fa fa-check";
});

onMounted((): void => {
    if (!(date_field.value instanceof HTMLInputElement)) {
        return;
    }
    datePicker(date_field.value, {
        static: true,
        onValueUpdate: (la_date, string_value) => {
            date.value = string_value;
        },
    });
});

const emit = defineEmits<{
    (e: "swap-mode"): void;
    (e: "validate-time", date: string, id: number, time: string, step: string): void;
}>();

const swapMode = (): void => {
    emit("swap-mode");
};

const validateNewTime = (): void => {
    if (TIME_REGEX.test(time.value)) {
        if (is_loading.value) {
            return;
        }
        is_loading.value = true;

        const id = props.timeData && props.timeData.id ? props.timeData.id : props.artifact.id;
        emit("validate-time", date.value, id, time.value, step.value);
    } else {
        error_message.value = $gettext("Please check time's format (hh:mm)");
        if (!time.value) {
            error_message.value = $gettext("Time is required");
        }
    }
};

defineExpose({ date, error_message, is_loading });
</script>

<style scoped lang="scss">
.timetracking-details-modal-buttons {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.timetracking-details-form-element {
    margin: 0;
}

.timetracking-tooltip {
    &::before {
        opacity: 1;
    }

    &::after {
        opacity: 1;
    }
}

.tlp-input.timetracking-details-modal-add-step-field {
    width: 100%;
}
</style>
