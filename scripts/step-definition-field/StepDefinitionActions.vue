<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="ttm-definition-step-actions">
        <span>
            <translate>Format:</translate>
            <select
                ref="format"
                class="input-small ttm-definition-step-description-format"
                v-on:change="input($event)"
                v-bind:disabled="disabled"
            >
                <option value="text" v-bind:selected="is_text">Text</option>
                <option value="html" v-bind:selected="!is_text">HTML</option>
            </select>
        </span>
        <slot />
    </div>
</template>

<script>
import { TEXT_FORMAT_TEXT } from "../../../tracker/scripts/constants/fields-constants.js";

export default {
    name: "StepDefinitionActions",
    props: {
        value: String,
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        is_text() {
            return this.value === TEXT_FORMAT_TEXT;
        },
    },
    methods: {
        input(event) {
            this.$emit("input", event, this.$refs.format.value);
        },
    },
};
</script>
