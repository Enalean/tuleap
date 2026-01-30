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
            {{ $gettext("Format:") }}
            <select
                v-bind:id="format_select_id"
                ref="format"
                class="small ttm-definition-step-description-format"
                v-on:change="input($event)"
                v-bind:disabled="disabled_format_selectbox"
                data-test="ttm-definition-step-description-format"
            >
                <option
                    value="text"
                    v-bind:selected="is_text"
                    data-test="ttm-definition-step-description-format-text"
                >
                    Text
                </option>
                <option
                    value="html"
                    v-bind:selected="is_html"
                    data-test="ttm-definition-step-description-format-html"
                >
                    HTML
                </option>
                <option
                    value="commonmark"
                    v-bind:selected="is_commonmark"
                    data-test="ttm-definition-step-description-format-commonmark"
                >
                    Markdown
                </option>
            </select>
            <commonmark-preview-button
                v-on:commonmark-preview-event="$emit('interpret-content-event')"
                v-bind:is_in_preview_mode="is_in_preview_mode"
                v-bind:is_preview_loading="is_preview_loading"
                v-if="is_commonmark_button_displayed"
            />
            <commonmark-syntax-helper
                v-bind:is_in_preview_mode="is_in_preview_mode"
                v-if="is_commonmark_button_displayed"
            />
        </div>
        <step-deletion-action-button-unmark-deletion v-bind:step="step" v-if="step.is_deleted" />
        <step-deletion-action-button-mark-as-deleted v-bind:step="step" v-else />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import CommonmarkSyntaxHelper from "./CommonMark/CommonmarkSyntaxHelper.vue";
import CommonmarkPreviewButton from "./CommonMark/CommonmarkPreviewButton.vue";
import StepDeletionActionButtonUnmarkDeletion from "./StepDeletionActionButtonUnmarkDeletion.vue";
import StepDeletionActionButtonMarkAsDeleted from "./StepDeletionActionButtonMarkAsDeleted.vue";
import type { Step } from "./Step";

const props = defineProps<{
    step: Step;
    disabled: boolean;
    format_select_id: string;
    is_in_preview_mode: boolean;
    is_preview_loading: boolean;
}>();

const format = ref<TextFieldFormat>();

const is_text = computed(() => props.step.description_format === TEXT_FORMAT_TEXT);
const is_html = computed(() => props.step.description_format === TEXT_FORMAT_HTML);
const is_commonmark = computed(() => props.step.description_format === TEXT_FORMAT_COMMONMARK);
const disabled_format_selectbox = computed(() => props.disabled || props.is_in_preview_mode);
const is_commonmark_button_displayed = computed(() => !props.disabled && is_commonmark.value);

const emit = defineEmits<{
    (e: "input", event: Event, format: TextFieldFormat): void;
    (e: "interpret-content-event"): void;
}>();

function input(event: Event) {
    if (!format.value) {
        return;
    }
    emit("input", event, format.value);
}
</script>
