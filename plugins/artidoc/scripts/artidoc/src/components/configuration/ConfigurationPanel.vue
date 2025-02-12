<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <form class="tlp-pane" v-on:submit="onSubmit">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    {{ pane_title }}
                </h1>
            </div>

            <section class="tlp-pane-section">
                <introductory-text v-bind:configuration_helper="configuration_helper" />
                <tracker-selection
                    v-bind:configuration_helper="configuration_helper"
                    v-bind:disabled="false"
                />
            </section>

            <section class="tlp-pane-section tlp-pane-section-submit">
                <error-feedback
                    class="artidoc-configuration-feedback"
                    v-if="is_error"
                    v-bind:error_message="error_message"
                />

                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-large artidoc-configuration-submit-button"
                    v-bind:disabled="is_submit_button_disabled"
                    data-test="artidoc-configuration-submit-button"
                >
                    <i
                        class="tlp-button-icon"
                        v-bind:class="submit_button_icon"
                        aria-hidden="true"
                    ></i>
                    {{ $gettext("Save configuration") }}
                </button>
            </section>
        </div>
    </form>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import IntroductoryText from "@/components/configuration/IntroductoryText.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import { TITLE } from "@/title-injection-key";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";

const { $gettext, interpolate } = useGettext();

const title = strictInject(TITLE);

const pane_title = interpolate($gettext("Configuration of %{ title }"), { title });

const configuration_helper = useConfigurationScreenHelper(strictInject(CONFIGURATION_STORE));

const { is_submit_button_disabled, submit_button_icon, is_error, error_message } =
    configuration_helper;

function onSubmit(event: Event): void {
    configuration_helper.onSubmit(event);
}
</script>

<style scoped lang="scss">
.tlp-pane-section-submit {
    flex-direction: column;
    align-items: center;
}

.artidoc-configuration-feedback {
    width: 100%;
}

.artidoc-configuration-submit-button {
    width: min-content;
}
</style>
