<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="project-registration-template-selection">
        <div class="project-registration-template-selection-tabs">
            <div
                v-if="tuleap_templates.length > 0"
                v-on:click="setSelectedTemplateCategory(CATEGORY_TULEAP)"
                v-bind:class="getTabsClasses(CATEGORY_TULEAP)"
                data-test="project-registration-tuleap-templates-tab"
            >
                Tuleap
            </div>
            <div
                v-if="company_templates.length > 0"
                v-on:click="setSelectedTemplateCategory(CATEGORY_ACME)"
                v-bind:class="getTabsClasses(CATEGORY_ACME)"
                data-test="project-registration-acme-templates-tab"
            >
                {{ platform_template_name }}
            </div>
            <div
                v-on:click="setSelectedTemplateCategory(CATEGORY_ADVANCED)"
                v-bind:class="getTabsClasses(CATEGORY_ADVANCED)"
                data-test="project-registration-advanced-templates-tab"
                v-translate
            >
                For advanced users
            </div>
        </div>
        <tuleap-template-list v-show="isTemplateCategorySelected(CATEGORY_TULEAP)" />
        <company-templates-list v-show="isTemplateCategorySelected(CATEGORY_ACME)" />
        <advanced-template-list v-show="isTemplateCategorySelected(CATEGORY_ADVANCED)" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace } from "vuex-class";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";
import CompanyTemplatesList from "./Company/CompanyTemplateList.vue";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import type { TemplateData } from "../../type";

const configuration = namespace("configuration");

@Component({
    components: {
        AdvancedTemplateList,
        CompanyTemplatesList,
        TuleapTemplateList,
    },
})
export default class TemplateSelection extends Vue {
    private readonly CATEGORY_TULEAP = "Tuleap";
    private readonly CATEGORY_ACME = "ACME";
    private readonly CATEGORY_ADVANCED = "Advanced";

    private selected_template_category = "";

    @configuration.State
    company_name!: string;

    @configuration.State
    tuleap_templates!: TemplateData[];

    @configuration.State
    company_templates!: TemplateData[];

    mounted(): void {
        if (this.tuleap_templates.length > 0) {
            this.selected_template_category = this.CATEGORY_TULEAP;

            return;
        }

        if (this.company_templates.length > 0) {
            this.selected_template_category = this.CATEGORY_ACME;

            return;
        }

        this.selected_template_category = this.CATEGORY_ADVANCED;
    }

    setSelectedTemplateCategory(template_category: string): void {
        this.selected_template_category = template_category;
    }

    isTemplateCategorySelected(template_category: string): boolean {
        return this.selected_template_category === template_category;
    }

    getTabsClasses(template_category: string): string {
        if (this.isTemplateCategorySelected(template_category)) {
            return "templates-category-tab active";
        }

        return "templates-category-tab";
    }

    get platform_template_name(): string {
        if (this.company_name === "Tuleap") {
            return this.$gettext("Custom templates");
        }
        return this.company_name;
    }
}
</script>
