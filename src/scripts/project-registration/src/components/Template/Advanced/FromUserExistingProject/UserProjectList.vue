<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element">
        <span v-if="props.project_list.length === 0" data-test="no-project-list">
            {{ $gettext("You are not administrator of any project.") }}
        </span>
        <select
            class="tlp-select tlp-select-adjusted user-project-list-select"
            id="from-another-project"
            data-test="from-another-project"
            name="from-another-project"
            v-on:change="storeSelectedTemplate"
            v-else
        >
            <option disabled value="" v-bind:selected="selected_project.id === default_option.id">
                {{ $gettext("Please choose a project...") }}
            </option>
            <option
                v-for="project in props.project_list"
                v-bind:value="project.id"
                v-bind:key="project.id"
                v-bind:data-test="`select-project-${project.id}`"
                v-bind:selected="project.id === selected_project.id"
            >
                {{ project.title }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { onMounted, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import type { TemplateData } from "../../../../type";
import { useStore } from "../../../../stores/root";

const { $gettext } = useGettext();
const root_store = useStore();

const default_option = {
    title: "",
    description: "",
    id: "",
    glyph: "",
    is_built_in: false,
};

const selected_project: Ref<TemplateData> = ref(default_option);

const props = defineProps<{
    project_list: Array<TemplateData>;
    selected_company_template: null | TemplateData;
}>();

watch(
    () => props.selected_company_template,
    (): void => {
        if (props.selected_company_template === null) {
            selected_project.value = default_option;
        }
    },
);

onMounted((): void => {
    if (props.selected_company_template !== null) {
        selected_project.value = props.selected_company_template;
    }
});

function storeSelectedTemplate(event: Event): void {
    if (event.target instanceof HTMLSelectElement) {
        const selected_template_id: number = Number.parseInt(event.target.value, 10);
        const selected_template = props.project_list.find(
            (project: TemplateData) => selected_template_id === Number.parseInt(project.id, 10),
        );
        if (selected_template === undefined) {
            return;
        }

        root_store.setSelectedTemplate(selected_template);
    }
}
</script>
