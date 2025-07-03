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
    <step-layout next_step_name="step-2">
        <template #step_info>
            <step-one-info />
        </template>

        <template #interactive_content v-if="store.state.project_templates.length > 0">
            <h3 data-test="platform-template-name">
                {{ title_company_name }}
            </h3>
            <div class="tracker-creation-starting-point-options">
                <tracker-template-card />
            </div>
        </template>

        <template #interactive_content_default>
            <h3>{{ default_templates_title }}</h3>
            <default-template-section />
        </template>

        <template #interactive_content_advanced>
            <h3>{{ advanced_users_title }}</h3>
            <div class="tracker-creation-starting-point-options">
                <tracker-from-another-project-card />
                <tracker-xml-file-card />
                <tracker-empty-card />
                <tracker-from-jira-card />
            </div>
        </template>
    </step-layout>
</template>
<script setup lang="ts">
import { computed, onMounted } from "vue";
import TrackerTemplateCard from "./cards/TrackerTemplate/TrackerTemplateCard.vue";
import TrackerXmlFileCard from "./cards/TrackerXmlFile/TrackerXmlFileCard.vue";
import StepLayout from "../layout/StepLayout.vue";
import StepOneInfo from "./StepOneInfo.vue";
import TrackerEmptyCard from "./cards/TrackerEmpty/TrackerEmptyCard.vue";
import TrackerFromAnotherProjectCard from "./cards/TrackerFromAnotherProject/TrackerFromAnotherProjectCard.vue";
import DefaultTemplateSection from "./cards/DefaultTemplate/DefaultTemplateSection.vue";
import TrackerFromJiraCard from "./cards/FromJira/TrackerFromJiraCard.vue";
import { useState, useStore } from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const { company_name } = useState(["company_name"]);
const store = useStore();

const { interpolate, $gettext, $ngettext } = useGettext();

onMounted(() => {
    store.commit("setSlugifyShortnameMode", true);
});

const title_company_name = computed(() => {
    return company_name.value === "Tuleap"
        ? $gettext("Custom templates")
        : interpolate($gettext("%{ company_name } templates"), {
              company_name: company_name.value,
          });
});

const advanced_users_title = $gettext("For advanced users");

const default_templates_title = computed(() => {
    return $ngettext("Tuleap template", "Tuleap templates", store.state.default_templates.length);
});
</script>
