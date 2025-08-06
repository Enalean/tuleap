<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tracker-creation-navigation">
        <div>
            <back-to-legacy v-if="are_there_tv3" data-test="back-to-legacy" />
        </div>

        <div class="tracker-creation-navigation-buttons">
            <router-link
                v-if="previous_step_name"
                v-bind:to="{ name: previous_step_name }"
                class="tracker-creation-previous-step-button"
                data-test="button-back"
            >
                <i class="fas fa-long-arrow-alt-left"></i>
                <span class="tracker-creation-previous-step-button-text">
                    {{ $gettext("Back") }}
                </span>
            </router-link>

            <button
                v-if="next_step_name"
                v-on:click="goToNextStepIfGood"
                class="tlp-button-primary tlp-button-large tracker-creation-next-step-button"
                type="button"
                v-bind:class="{ 'tlp-button-disabled': !is_ready_for_step_2 }"
                v-bind:disabled="!is_ready_for_step_2"
                data-test="button-next"
            >
                {{ $gettext("Next") }}
                <i
                    class="fa tlp-button-icon"
                    v-bind:class="{
                        'fa-long-arrow-right ': !is_parsing_a_xml_file,
                        'fa-circle-o-notch fa-spin': is_parsing_a_xml_file,
                    }"
                ></i>
            </button>
            <button
                v-else
                class="tlp-button-primary tlp-button-large tracker-creation-submit-button"
                type="submit"
                data-test="button-create-my-tracker"
                v-bind:class="{
                    'tlp-button-disabled': !is_ready_to_submit || has_form_been_submitted,
                }"
                v-bind:disabled="!is_ready_to_submit || has_form_been_submitted"
                v-on:click="submitSetCreationFormHasBeenSubmitted"
            >
                {{ $gettext("Create my tracker") }}
                <i
                    class="tlp-button-icon fa"
                    v-bind:class="{
                        'fa-circle-o-notch fa-spin': has_form_been_submitted,
                        'fa-arrow-circle-o-right': !has_form_been_submitted,
                    }"
                ></i>
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import BackToLegacy from "./BackToLegacy.vue";
import { useState, useGetters, useMutations } from "vuex-composition-helpers";
import { useRouter } from "../../../helpers/use-router";
const props = defineProps<{
    next_step_name?: string | undefined;
    previous_step_name?: string | undefined;
}>();

const { has_form_been_submitted, is_parsing_a_xml_file, are_there_tv3 } = useState([
    "has_form_been_submitted",
    "is_parsing_a_xml_file",
    "are_there_tv3",
]);

const { is_ready_for_step_2, is_ready_to_submit } = useGetters([
    "is_ready_for_step_2",
    "is_ready_to_submit",
]);

const { setCreationFormHasBeenSubmitted } = useMutations(["setCreationFormHasBeenSubmitted"]);

const router = useRouter();

function goToNextStepIfGood(): void {
    if (is_ready_for_step_2.value && props.next_step_name) {
        router.push({ name: props.next_step_name });
    }
}

function submitSetCreationFormHasBeenSubmitted(): void {
    setCreationFormHasBeenSubmitted();
}
</script>
