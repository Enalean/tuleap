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
<script>
import { datePicker } from "tlp";

export default {
    name: "DateFlatPicker",
    model: {
        prop: "value",
    },
    props: {
        id: {
            type: String,
            required: true,
        },
        required: {
            type: Boolean,
            default: false,
        },
        value: {
            default: null,
            required: true,
            validator: (value) => typeof value === "string" || value === null,
        },
    },
    data() {
        return { datepicker: null, input_value: this.value };
    },
    watch: {
        value(newValue) {
            if (this.datepicker === null) {
                return;
            }
            if (newValue) {
                this.datepicker.setDate(newValue, false);
            }
        },
    },
    mounted() {
        this.datepicker = datePicker(this.$el, {
            defaultDate: this.value,
            onChange: this.onDatePickerChange,
        });
    },
    beforeDestroy() {
        if (this.datepicker === null) {
            return;
        }
        this.datepicker.destroy();
        this.datepicker = null;
    },
    methods: {
        onDatePickerInput(event) {
            this.$emit("input", event.target.value);
        },
        onDatePickerChange() {
            this.$nextTick(() => {
                this.$emit("input", this.$el.value);
            });
        },
    },
};
</script>
