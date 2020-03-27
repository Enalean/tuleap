<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <input
        class="tlp-input"
        type="number"
        step="any"
        v-bind:value="value"
        v-on:input="onInput"
        v-bind:disabled="disabled"
        required
    />
</template>

<script>
export default {
    name: "FloatInput",
    props: {
        value: {
            type: Number,
        },
        disabled: {
            type: Boolean,
        },
    },
    methods: {
        onInput(event) {
            let float_value = parseFloat(event.target.value);
            if (isNaN(float_value)) {
                return;
            }
            return this.$emit("input", float_value);
        },
    },
};
</script>
