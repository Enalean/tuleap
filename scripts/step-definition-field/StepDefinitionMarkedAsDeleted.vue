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
            v-bind:value="step.description_format"
            v-bind:disabled="true"
        >
            <step-deletion-action-button-unmark-deletion
                v-bind:unmark-deletion="unmarkDeletion"
                v-bind:is_deletion="false"
            />
        </step-definition-actions>
        <div class="ttm-definition-step-description-deleted">
            <div
                v-dompurify-html="step.raw_description"
                v-bind:class="{ 'ttm-definition-step-description-text': is_text }"
            ></div>
            <section class="ttm-definition-step-expected">
                <step-definition-arrow-expected/>
                <div class="ttm-definition-step-expected-edit">
                    <div class="ttm-definition-step-expected-edit-title">
                        <translate>Expected results</translate>
                    </div>
                    <div
                        v-dompurify-html="step.raw_expected_results"
                        v-bind:class="{ 'ttm-definition-step-description-text': is_text }"
                    ></div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import StepDeletionActionButtonUnmarkDeletion from "./StepDeletionActionButtonUnmarkDeletion.vue";
import StepDefinitionArrowExpected from "./StepDefinitionArrowExpected.vue";
import { TEXT_FORMAT_TEXT } from "../../../tracker/www/scripts/constants/fields-constants.js";
import StepDefinitionActions from "./StepDefinitionActions.vue";
export default {
    name: "StepDefinitionMarkedAsDeleted",
    components: { StepDefinitionActions, StepDefinitionArrowExpected, StepDeletionActionButtonUnmarkDeletion },
    props: {
        step: Object
    },
    computed: {
        is_text() {
            return this.step.description_format === TEXT_FORMAT_TEXT;
        }
    },
    methods: {
        unmarkDeletion() {
            this.$emit("unmarkDeletion");
        }
    }
};
</script>
