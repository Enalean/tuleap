<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="project-registration-templates" v-if="company_templates.length > 0">
        <h3 data-test="project-registration-company-template-title">
            {{ platform_template_name }}
        </h3>

        <section class="project-registration-default-templates-section">
            <template-card
                v-for="template of company_templates"
                v-bind:key="template.id"
                v-bind:template="template"
            />
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import TemplateCard from "../TemplateCard.vue";
import { State } from "vuex-class";
import { TemplateData } from "../../../type";
import { sprintf } from "sprintf-js";

@Component({
    components: { TemplateCard },
})
export default class TuleapCompanyTemplateList extends Vue {
    @State
    company_templates!: TemplateData[];

    @State
    company_name!: string;

    get platform_template_name(): string {
        if (this.company_name === "Tuleap") {
            return this.$gettext("Custom templates");
        }
        return sprintf(this.$gettext("%s templates"), this.company_name);
    }
}
</script>
