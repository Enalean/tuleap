<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <input
        type="text"
        class="tlp-input tlp-input-date"
        size="12"
        v-bind:id="id"
        v-bind:required="required"
        v-on:input="onDatePickerInput"
        v-model="input_value"
        ref="root"
    />
</template>
<script setup lang="ts">
import type { DatePickerInstance } from "tlp";
import { datePicker } from "tlp";
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps<{
    id: string;
    required: boolean;
    value: string;
}>();

const datepicker = ref<DatePickerInstance | null>(null);
const input_value = ref(props.value);
const root = ref<InstanceType<typeof HTMLElement>>();

watch(
    () => input_value.value,
    (value: string): void => {
        if (datepicker.value === null) {
            return;
        }
        if (value) {
            datepicker.value.setDate(value, false);
        }
    },
    { immediate: true },
);

onMounted((): void => {
    const element = root.value;
    if (element instanceof HTMLInputElement) {
        datepicker.value = datePicker(element, {
            defaultDate: props.value,
            onChange: onDatePickerChange,
            allowInput: true,
            errorHandler: (error) => {
                if (error.message.includes("Invalid date provided")) {
                    return;
                }
                throw error;
            },
        });
    }
});
onBeforeUnmount((): void => {
    if (datepicker.value === null) {
        return;
    }
    datepicker.value.destroy();
    datepicker.value = null;
});

const emit = defineEmits<{
    (e: "input", value: string): void;
}>();

function onDatePickerInput(event: Event) {
    if (event.target instanceof HTMLInputElement) {
        emit("input", event.target.value);
    }
}

async function onDatePickerChange(): Promise<void> {
    const element = root.value;
    if (element instanceof HTMLInputElement) {
        await nextTick();
        emit("input", element.value);
    }
}
</script>
