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
    />
</template>
<script lang="ts">
import type { DatePickerInstance } from "tlp";
import { datePicker } from "tlp";
import { Component, Prop, Vue, Watch } from "vue-property-decorator";

@Component
export default class DateFlatPicker extends Vue {
    @Prop({ required: true })
    readonly id!: string;

    @Prop({ required: true })
    readonly required!: boolean;

    @Prop({ required: true })
    readonly value!: string;

    private datepicker: null | DatePickerInstance = null;
    private input_value = this.value;

    @Watch("value")
    public updateValue(new_value: string): void {
        if (this.datepicker === null) {
            return;
        }
        if (new_value) {
            this.datepicker.setDate(new_value, false);
        }
    }

    mounted(): void {
        const element = this.$el;
        if (element instanceof HTMLInputElement) {
            this.datepicker = datePicker(element, {
                defaultDate: this.value,
                onChange: this.onDatePickerChange,
            });
        }
    }
    beforeDestroy(): void {
        if (this.datepicker === null) {
            return;
        }
        this.datepicker.destroy();
        this.datepicker = null;
    }

    onDatePickerInput(event: Event) {
        if (event.target instanceof HTMLInputElement) {
            this.$emit("input", event.target.value);
        }
    }
    onDatePickerChange(): void {
        const element = this.$el;
        if (element instanceof HTMLInputElement) {
            this.$nextTick(() => {
                this.$emit("input", element.value);
            });
        }
    }
}
</script>
