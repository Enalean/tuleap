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
                v-if="root_store.company_templates.length > 0"
                v-on:click.prevent="setSelectedTemplateCategory(CATEGORY_ACME)"
                v-bind:class="getTabsClasses(CATEGORY_ACME)"
                data-test="project-registration-acme-templates-tab"
                v-bind:href="'#' + CATEGORY_ACME"
            >
                {{ platform_template_name }}
            </a>
            <a
                v-if="root_store.tuleap_templates.length > 0"
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
            >
                {{ $gettext("For advanced users") }}
            </a>
        </nav>
        <tuleap-template-list v-show="isTemplateCategorySelected(CATEGORY_TULEAP)" />
        <company-templates-list v-show="isTemplateCategorySelected(CATEGORY_ACME)" />
        <advanced-template-list v-show="isTemplateCategorySelected(CATEGORY_ADVANCED)" />
        <categorised-external-templates-list
            v-for="category_name in categorised_external_templates_map.keys()"
            v-bind:key="category_name"
            v-show="isTemplateCategorySelected(category_name)"
            v-bind:templates="getTemplateSelected(category_name)"
        />
    </div>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";
import CompanyTemplatesList from "./Company/CompanyTemplateList.vue";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import CategorisedExternalTemplatesList from "./CategorisedExternalTemplates/CategorisedExternalTemplatesList.vue";
import type { ExternalTemplateCategory, ExternalTemplateData } from "../../type";
import { useStore } from "../../stores/root";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const CATEGORY_TULEAP = "Tuleap";
const CATEGORY_ACME = "ACME";
const CATEGORY_ADVANCED = "Advanced";

const external_templates_categories: Ref<ExternalTemplateCategory[]> = ref([]);
const categorised_external_templates_map: Ref<Map<string, ExternalTemplateData[]>> = ref(new Map());

const root_store = useStore();
onMounted(() => {
    root_store.external_templates.forEach((template) => {
        const category_templates = categorised_external_templates_map.value.get(
            template.template_category.shortname,
        );
        if (category_templates) {
            category_templates.push(template);
            return;
        }
        external_templates_categories.value.push(template.template_category);
        categorised_external_templates_map.value.set(template.template_category.shortname, [
            template,
        ]);
    });

    if (root_store.selected_template_category !== null) {
        return;
    }

    if (root_store.company_templates.length > 0) {
        setSelectedTemplateCategory(CATEGORY_ACME);
        return;
    }

    if (root_store.tuleap_templates.length > 0) {
        setSelectedTemplateCategory(CATEGORY_TULEAP);
        return;
    }

    const first_external_template_category = categorised_external_templates_map.value
        .keys()
        .next().value;
    if (first_external_template_category) {
        setSelectedTemplateCategory(first_external_template_category);
        return;
    }

    setSelectedTemplateCategory(CATEGORY_ADVANCED);
});

function setSelectedTemplateCategory(template_category: string): void {
    root_store.resetSelectedTemplate();
    root_store.setSelectedTemplateCategory(template_category);
}

function isTemplateCategorySelected(template_category: string): boolean {
    return root_store.selected_template_category === template_category;
}

function getTemplateSelected(template_category: string): ExternalTemplateData[] {
    let external_template_data = categorised_external_templates_map.value.get(template_category);
    if (external_template_data === undefined) {
        return [];
    }
    return external_template_data;
}

function getTabsClasses(template_category: string): string[] {
    const classes = ["tlp-tab"];

    if (isTemplateCategorySelected(template_category)) {
        classes.push("tlp-tab-active");
    }

    return classes;
}

function getExternalCategoryClasses(category: ExternalTemplateCategory): string[] {
    const classes = getTabsClasses(category.shortname);

    if (category.should_case_of_label_be_respected) {
        classes.push("templates-category-tab-with-mixed-case");
    }

    return classes;
}

const platform_template_name = computed((): string => {
    if (root_store.company_name === "Tuleap") {
        return $gettext("Custom templates");
    }
    return root_store.company_name;
});
</script>
