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
    <step-layout next-step-name="step-2">
        <template v-slot:step_info>
            <step-one-info />
        </template>

        <template v-slot:interactive_content>
            <h3 data-test="platform-template-name">{{ title_company_name }}</h3>
            <div class="tracker-creation-starting-point-options">
                <tracker-template-card />
                <tracker-from-another-project-card />
            </div>
        </template>

        <template v-slot:interactive_content_advanced>
            <h3>{{ advanced_users_title }}</h3>
            <div class="tracker-creation-starting-point-options">
                <tracker-xml-file-card />
                <tracker-empty-card />
            </div>
        </template>
    </step-layout>
</template>
<script lang="ts">
import Vue from "vue";
import { Mutation, State } from "vuex-class";
import { Component } from "vue-property-decorator";
import TrackerTemplateCard from "./cards/TrackerTemplate/TrackerTemplateCard.vue";
import TrackerXmlFileCard from "./cards/TrackerXmlFile/TrackerXmlFileCard.vue";
import StepLayout from "../layout/StepLayout.vue";
import StepOneInfo from "./StepOneInfo.vue";
import TrackerEmptyCard from "./cards/TrackerEmpty/TrackerEmptyCard.vue";
import TrackerFromAnotherProjectCard from "./cards/TrackerFromAnotherProject/TrackerFromAnotherProjectCard.vue";
import { sprintf } from "sprintf-js";

@Component({
    components: {
        TrackerEmptyCard,
        StepLayout,
        TrackerTemplateCard,
        TrackerXmlFileCard,
        TrackerFromAnotherProjectCard,
        StepOneInfo,
    },
})
export default class StepOne extends Vue {
    @Mutation
    readonly setSlugifyShortnameMode!: (is_active: boolean) => void;

    @State
    readonly company_name!: string;

    private title_company_name = "";

    mounted(): void {
        this.setSlugifyShortnameMode(true);

        if (this.company_name === "Tuleap") {
            this.title_company_name = this.$gettext("Custom templates");
        } else {
            this.title_company_name = sprintf(this.$gettext("%s templates"), this.company_name);
        }
    }

    get advanced_users_title(): string {
        return this.$gettext("For advanced users");
    }
}
</script>
