<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  -
  -->

<template>
    <select
        class="tlp-select"
        v-on:change="onChange"
        v-bind:disabled="disabled"
        required
        data-test="select-date"
    >
        <option
            data-test-type="placeholder"
            v-bind:value="null"
            disabled
            v-bind:selected="input_value === null"
        >
            {{ $gettext("Please choose") }}
        </option>
        <option
            data-test-type="clear"
            v-bind:value="DATE_FIELD_VALUE.CLEAR"
            v-bind:selected="input_value === DATE_FIELD_VALUE.CLEAR"
        >
            {{ $gettext("Clear") }}
        </option>
        <option
            data-test-type="current"
            v-bind:value="DATE_FIELD_VALUE.CURRENT"
            v-bind:selected="input_value === DATE_FIELD_VALUE.CURRENT"
        >
            {{ $gettext("Current time") }}
        </option>
    </select>
</template>

<script>
import { DATE_FIELD_VALUE } from "../../../constants/workflow-constants.js";
export default {
    name: "DateInput",
    emits: ["change"],
    props: {
        input_value: {
            type: String,
        },
        disabled: {
            type: Boolean,
        },
    },
    data() {
        return {
            DATE_FIELD_VALUE,
        };
    },
    computed: {
        field_value() {
            return this.value;
        },
    },
    methods: {
        onChange(event) {
            this.$emit("change", event.target.value);
        },
    },
};
</script>
