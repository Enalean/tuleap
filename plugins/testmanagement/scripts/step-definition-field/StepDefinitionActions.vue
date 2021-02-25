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
        <div class="ttm-definition-step-actions-format-and-helper-container">
            <translate>Format:</translate>
            <select
                v-bind:id="format_select_id"
                ref="format"
                class="input-small ttm-definition-step-description-format"
                v-on:change="input($event)"
                v-bind:disabled="disabled"
            >
                <option value="text" v-bind:selected="is_text">Text</option>
                <option value="html" v-bind:selected="is_html">HTML</option>
                <option value="commonmark" v-bind:selected="is_commonmark">Markdown</option>
            </select>
            <commonmark-syntax-helper v-if="is_commonmark" />
        </div>
        <slot />
    </div>
</template>

<script>
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "../../../tracker/scripts/constants/fields-constants.js";
import { mapState } from "vuex";
import CommonmarkSyntaxHelper from "./CommonmarkSyntaxHelper.vue";

export default {
    name: "StepDefinitionActions",
    components: { CommonmarkSyntaxHelper },
    props: {
        value: String,
        disabled: {
            type: Boolean,
            default: false,
        },
        format_select_id: {
            type: String,
            default: "",
        },
    },
    computed: {
        ...mapState(["field_id"]),
        is_text() {
            return this.value === TEXT_FORMAT_TEXT;
        },
        is_html() {
            return this.value === TEXT_FORMAT_HTML;
        },
        is_commonmark() {
            return this.value === TEXT_FORMAT_COMMONMARK;
        },
    },
    methods: {
        input(event) {
            this.$emit("input", event, this.$refs.format.value);
        },
    },
};
</script>
