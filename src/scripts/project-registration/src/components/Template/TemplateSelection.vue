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
        <nav class="project-registration-template-selection-tabs tlp-tabs tlp-tabs-vertical">
            <a
                v-if="company_templates.length > 0"
                v-on:click.prevent="setSelectedTemplateCategory(CATEGORY_ACME)"
                v-bind:class="getTabsClasses(CATEGORY_ACME)"
                data-test="project-registration-acme-templates-tab"
                v-bind:href="'#' + CATEGORY_ACME"
            >
                {{ platform_template_name }}
            </a>
            <a
                v-if="tuleap_templates.length > 0"
                v-on:click.prevent="setSelectedTemplateCategory(CATEGORY_TULEAP)"
                v-bind:class="getTabsClasses(CATEGORY_TULEAP)"
                data-test="project-registration-tuleap-templates-tab"
                v-bind:href="'#' + CATEGORY_TULEAP"
            >
                Tuleap
            </a>
            <a
                v-for="category in external_templates_categories"
                v-bind:key="'tab-' + category.shortname"
                v-on:click.prevent="setSelectedTemplateCategory(category.shortname)"
                v-bind:class="getExternalCategoryClasses(category)"
                v-bind:data-test="'project-registration-' + category.shortname + '-templates-tab'"
                v-bind:href="'#' + category.shortname"
            >
                {{ category.label }}
            </a>
            <a
                v-on:click.prevent="setSelectedTemplateCategory(CATEGORY_ADVANCED)"
                v-bind:class="getTabsClasses(CATEGORY_ADVANCED)"
                data-test="project-registration-advanced-templates-tab"
                v-bind:href="'#' + CATEGORY_ADVANCED"
                v-translate
            >
                For advanced users
            </a>
        </nav>
        <tuleap-template-list v-show="isTemplateCategorySelected(CATEGORY_TULEAP)" />
        <company-templates-list v-show="isTemplateCategorySelected(CATEGORY_ACME)" />
        <advanced-template-list v-show="isTemplateCategorySelected(CATEGORY_ADVANCED)" />
        <categorised-external-templates-list
            v-for="category_name in categorised_external_templates_map.keys()"
            v-bind:key="category_name"
            v-show="isTemplateCategorySelected(category_name)"
            v-bind:templates="categorised_external_templates_map.get(category_name)"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";
import CompanyTemplatesList from "./Company/CompanyTemplateList.vue";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import CategorisedExternalTemplatesList from "./CategorisedExternalTemplates/CategorisedExternalTemplatesList.vue";
import type { ExternalTemplateCategory, ExternalTemplateData, TemplateData } from "../../type";

const configuration = namespace("configuration");

@Component({
    components: {
        AdvancedTemplateList,
        CompanyTemplatesList,
        TuleapTemplateList,
        CategorisedExternalTemplatesList,
    },
})
export default class TemplateSelection extends Vue {
    readonly CATEGORY_TULEAP = "Tuleap";
    readonly CATEGORY_ACME = "ACME";
    readonly CATEGORY_ADVANCED = "Advanced";

    external_templates_categories: ExternalTemplateCategory[] = [];
    categorised_external_templates_map = new Map<string, ExternalTemplateData[]>();

    @configuration.State
    company_name!: string;

    @configuration.State
    tuleap_templates!: TemplateData[];

    @configuration.State
    company_templates!: TemplateData[];

    @configuration.State
    external_templates!: ExternalTemplateData[];

    @State
    selected_template_category!: string | null;

    mounted(): void {
        this.external_templates.forEach((template) => {
            const category_templates = this.categorised_external_templates_map.get(
                template.template_category.shortname,
            );
            if (category_templates) {
                category_templates.push(template);
                return;
            }
            this.external_templates_categories.push(template.template_category);
            this.categorised_external_templates_map.set(template.template_category.shortname, [
                template,
            ]);
        });

        if (this.selected_template_category !== null) {
            return;
        }

        if (this.company_templates.length > 0) {
            this.setSelectedTemplateCategory(this.CATEGORY_ACME);
            return;
        }

        if (this.tuleap_templates.length > 0) {
            this.setSelectedTemplateCategory(this.CATEGORY_TULEAP);
            return;
        }

        const first_external_template_category = this.categorised_external_templates_map
            .keys()
            .next().value;
        if (first_external_template_category) {
            this.setSelectedTemplateCategory(first_external_template_category);
            return;
        }

        this.setSelectedTemplateCategory(this.CATEGORY_ADVANCED);
    }

    setSelectedTemplateCategory(template_category: string): void {
        this.$store.commit("resetSelectedTemplate");
        this.$store.commit("setSelectedTemplateCategory", template_category);
    }

    isTemplateCategorySelected(template_category: string): boolean {
        return this.selected_template_category === template_category;
    }

    getTabsClasses(template_category: string): string[] {
        const classes = ["tlp-tab"];

        if (this.isTemplateCategorySelected(template_category)) {
            classes.push("tlp-tab-active");
        }

        return classes;
    }

    getExternalCategoryClasses(category: ExternalTemplateCategory): string[] {
        const classes = this.getTabsClasses(category.shortname);

        if (category.should_case_of_label_be_respected) {
            classes.push("templates-category-tab-with-mixed-case");
        }

        return classes;
    }

    get platform_template_name(): string {
        if (this.company_name === "Tuleap") {
            return this.$gettext("Custom templates");
        }
        return this.company_name;
    }
}
</script>
