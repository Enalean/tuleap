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
    <div class="project-registration-template-card">
        <input
            type="radio"
            id="project-registration-tuleap-template-other-user-project"
            class="project-registration-selected-template"
            v-bind:checked="root_store.is_advanced_option_selected(option_name)"
            name="selected-template"
            data-test="selected-template-input"
            v-on:change="root_store.setAdvancedActiveOption(option_name)"
        />

        <label
            class="tlp-card tlp-card-selectable project-registration-template-label"
            data-test="project-registration-card-label"
            for="project-registration-tuleap-template-other-user-project"
            v-on:click="loadProjects"
        >
            <div class="project-registration-template-glyph"><svg-template /></div>
            <div class="project-registration-template-content">
                <h4 class="project-registration-template-card-title" v-translate>
                    From another project I'm admin of
                </h4>
                <div
                    v-if="
                        !root_store.is_advanced_option_selected(option_name) &&
                        !is_loading_project_list &&
                        !has_error
                    "
                    class="project-registration-template-card-description"
                    data-test="user-project-description"
                >
                    <translate>
                        Project configuration will be duplicated into your new project.
                    </translate>
                </div>
                <div
                    v-else-if="is_loading_project_list && !has_error"
                    data-test="user-project-spinner"
                >
                    <i class="fa fa-spinner fa-spin fa-circle-o-notch"></i>
                </div>
                <div v-else-if="has_error" class="tlp-text-danger" data-test="user-project-error">
                    <translate>Oh snap! Failed to load project you are admin of.</translate>
                </div>
                <user-project-list
                    v-else-if="root_store.is_advanced_option_selected(option_name)"
                    v-bind:project-list="root_store.projects_user_is_admin_of"
                    v-bind:selected-company-template="root_store.selected_company_template"
                    data-test="user-project-list"
                />
            </div>
        </label>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { AdvancedOptions } from "../../../../type";
import SvgTemplate from "./SvgTemplate.vue";
import UserProjectList from "./UserProjectList.vue";
import { useStore } from "../../../../stores/root";
const option_name: AdvancedOptions = "from_existing_user_project";

const is_loading_project_list = ref<boolean>(false);
const has_error = ref<boolean>(false);

const root_store = useStore();

async function loadProjects(): Promise<void> {
    if (root_store.projects_user_is_admin_of.length > 0) {
        is_loading_project_list.value = false;
        return;
    }

    has_error.value = false;
    is_loading_project_list.value = true;
    try {
        await root_store.loadUserProjects();
    } catch (error) {
        has_error.value = true;
        throw error;
    } finally {
        is_loading_project_list.value = false;
    }
}
</script>
