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
    <div>
        <step-definition-actions
            v-bind:step="step"
            v-bind:disabled="true"
            v-bind:format_select_id="''"
            v-bind:is_preview_loading="false"
            v-bind:is_in_preview_mode="false"
        />
        <div class="ttm-definition-step-description-deleted">
            <div
                v-dompurify-html="step.raw_description"
                v-bind:class="{
                    'ttm-definition-step-description-text':
                        step.description_format === TEXT_FORMAT_TEXT,
                }"
            ></div>
            <section class="ttm-definition-step-expected">
                <step-definition-arrow-expected />
                <div class="ttm-definition-step-expected-edit">
                    <div class="ttm-definition-step-expected-edit-title">
                        {{ $gettext("Expected results") }}
                    </div>
                    <div
                        v-dompurify-html="step.raw_expected_results"
                        v-bind:class="{
                            'ttm-definition-step-description-text':
                                step.description_format === TEXT_FORMAT_TEXT,
                        }"
                    ></div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";
import StepDefinitionArrowExpected from "./StepDefinitionArrowExpected.vue";
import StepDefinitionActions from "./StepDefinitionActions.vue";
import type { Step } from "./Step";

defineProps<{
    step: Step;
}>();
</script>
