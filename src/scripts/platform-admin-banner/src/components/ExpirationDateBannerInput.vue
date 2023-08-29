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
        <label for="expiration-date-picker-banner" class="tlp-label" v-translate>
            Expiration date
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

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import { datePicker } from "tlp";
import type { DatePickerInstance } from "tlp";

@Component
export default class ExpirationDateBannerInput extends Vue {
    @Prop({ required: true, type: String })
    readonly value!: string;

    private datepicker: DatePickerInstance | null = null;

    public mounted(): void {
        const input_field = this.$refs.input_field;
        if (!(input_field instanceof HTMLInputElement)) {
            throw new Error("The datepicker element is supposed to be an input field");
        }

        const now = new Date();
        const min_expiration_date = new Date(new Date(now).setHours(now.getHours() + 1));
        this.datepicker = datePicker(input_field, {
            minDate: min_expiration_date,
            onChange: this.onDatePickerChange,
        });
        this.updateDatePickerCurrentDate();
    }

    public beforeDestroy(): void {
        const datepicker = this.datepicker;
        if (datepicker === null) {
            return;
        }
        this.datepicker = null;
        datepicker.destroy();
    }

    @Watch("value")
    private updateDatePickerCurrentDate(): void {
        if (this.datepicker === null) {
            return;
        }
        this.datepicker.setDate(new Date(this.value), false);
    }

    onDatePickerInput(event: Event): void {
        const event_target = event.currentTarget;
        if (event_target instanceof HTMLInputElement) {
            this.$emit("input", event_target.value);
        }
    }

    onDatePickerChange(): void {
        this.$nextTick(() => {
            if (this.$refs.input_field instanceof HTMLInputElement) {
                this.$emit("input", this.$refs.input_field.value);
            }
        });
    }
}
</script>
