<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element">
        <label for="expiration-date-picker-banner" class="tlp-label">
            {{ $gettext("Expiration date") }}
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
            <input
                ref="input_field"
                type="text"
                id="expiration-date-picker-banner"
                class="tlp-input tlp-input-date"
                data-enabletime="true"
                size="19"
                v-on:input="onDatePickerInput"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import type { DatePickerInstance } from "@tuleap/tlp-date-picker";
import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";

const { $gettext } = useGettext();

const props = defineProps<{
    readonly value: string;
}>();

const emit = defineEmits<{
    (e: "input", value: string): void;
}>();

const input_field = ref<HTMLInputElement>();
let datepicker_instance: DatePickerInstance | null = null;

onMounted(() => {
    if (!input_field.value) {
        return;
    }
    const now = new Date();
    const min_expiration_date = new Date(new Date(now).setHours(now.getHours() + 1));
    datepicker_instance = createDatePicker(input_field.value, getLocaleWithDefault(document), {
        minDate: min_expiration_date,
        onChange: onDatePickerChange,
    });

    watch(() => props.value, updateDatePickerCurrentDate, { immediate: true });
});

onUnmounted(() => {
    datepicker_instance?.destroy();
});

function updateDatePickerCurrentDate(): void {
    if (props.value !== "") {
        datepicker_instance?.setDate(new Date(props.value), false);
    }
}

function onDatePickerInput(event: Event): void {
    const event_target = event.currentTarget;
    if (event_target instanceof HTMLInputElement) {
        emit("input", event_target.value);
    }
}

function onDatePickerChange(): void {
    nextTick(() => {
        if (input_field.value instanceof HTMLInputElement) {
            emit("input", input_field.value.value);
        }
    });
}
</script>
