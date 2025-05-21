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
    <div>
        <div
            class="tlp-alert-danger"
            v-if="root_store.has_error"
            data-test="project-creation-failed"
        >
            {{ root_store.error }}
        </div>

        <div class="project-registration-content">
            <form
                v-on:submit.prevent="createProject"
                class="project-registration-form"
                data-test="project-registration-form"
            >
                <div class="register-new-project-section">
                    <project-information-svg />
                    <div class="register-new-project-list register-new-project-information">
                        <h1 class="project-registration-title">
                            {{ $gettext("Start a new project") }}
                        </h1>

                        <nav class="tlp-wizard">
                            <router-link
                                v-bind:to="{ name: 'template' }"
                                v-on:click="resetProjectCreationError"
                                class="tlp-wizard-step-previous"
                            >
                                {{ $gettext("Template") }}
                            </router-link>
                            <span class="tlp-wizard-step-current">{{
                                $gettext("Information")
                            }}</span>
                        </nav>

                        <h2>
                            <span>{{ $gettext("Project information") }}</span>
                        </h2>
                        <div
                            class="register-new-project-information-form-container"
                            data-test="register-new-project-information-form"
                        ></div>
                        <div class="tlp-property">
                            <label class="tlp-label">{{ $gettext("Chosen template") }}</label>
                            <p class="project-information-selected-template">
                                {{ selected_template_name }}
                            </p>
                        </div>

                        <project-name v-model="name_properties" />

                        <div
                            class="tlp-form-element project-information-privacy"
                            v-if="root_store.can_user_choose_project_visibility"
                        >
                            <label
                                class="tlp-label"
                                for="project-information-input-privacy-list-label"
                            >
                                <span>{{ $gettext("Visibility") }}</span>
                                <i class="fa fa-asterisk"></i>
                            </label>
                            <project-information-input-privacy-list
                                data-test="register-new-project-information-list"
                            />
                        </div>

                        <field-description
                            v-bind:field_description_value="field_description"
                            v-on:input="(new_value) => (field_description = new_value)"
                        />
                        <trove-category-list
                            v-model="trove_cats"
                            v-for="trovecat in root_store.trove_categories"
                            v-bind:key="trovecat.id"
                            v-bind:trovecat="trovecat"
                        />
                        <fields-list
                            v-for="field in root_store.project_fields"
                            v-bind:key="field.group_desc_id + field.desc_name"
                            v-bind:field="field"
                        />
                        <policy-agreement />
                        <project-information-footer />
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Ref } from "vue";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import ProjectName from "./Input/ProjectName.vue";
import ProjectInformationInputPrivacyList from "./Input/ProjectInformationInputPrivacyList.vue";
import type {
    FieldProperties,
    ProjectNameProperties,
    ProjectProperties,
    ProjectVisibilityProperties,
    TroveCatProperties,
} from "../../type";
import emitter from "../../helpers/emitter";
import TroveCategoryList from "./TroveCat/TroveCategoryList.vue";
import FieldDescription from "./Fields/FieldDescription.vue";
import PolicyAgreement from "./Agreement/PolicyAgreement.vue";
import FieldsList from "./Fields/FieldsList.vue";
import { redirectToUrl } from "../../helpers/location-helper";
import { buildProjectPrivacy } from "../../helpers/privacy-builder";
import { useStore } from "../../stores/root";
import { useRouter } from "../../helpers/use-router";

const { $gettext } = useGettext();
const root_store = useStore();

const router = useRouter();

const selected_visibility = ref("");
const name_properties: Ref<ProjectNameProperties> = ref({
    slugified_name: "",
    name: "",
});
const field_description = ref("");
const trove_cats: Ref<Array<TroveCatProperties>> = ref([]);
const is_private = ref(false);
const selected_template_name = ref("");
const field_list: Ref<Array<FieldProperties>> = ref([]);

onMounted(() => {
    if (!root_store.is_template_selected) {
        router.push("new");
        return;
    }

    if (root_store.selected_tuleap_template) {
        selected_template_name.value = root_store.selected_tuleap_template.title;
    } else if (root_store.selected_company_template) {
        selected_template_name.value = root_store.selected_company_template.title;
    }

    selected_visibility.value = root_store.project_default_visibility;
    emitter.on("update-project-name", updateProjectName);
    emitter.on("choose-trove-cat", updateTroveCat);
    emitter.on("update-field-list", updateFieldList);
    emitter.on("update-project-visibility", updateProjectVisibility);
});

onBeforeUnmount((): void => {
    emitter.off("update-project-name", updateProjectName);
    emitter.off("choose-trove-cat", updateTroveCat);
    emitter.off("update-field-list", updateFieldList);
    emitter.off("update-project-visibility", updateProjectVisibility);
});

function updateProjectName(event: ProjectNameProperties): void {
    name_properties.value = event;
}

function updateTroveCat(event: TroveCatProperties): void {
    const index = trove_cats.value.findIndex((trove) => trove.category_id === event.category_id);
    if (index === -1) {
        trove_cats.value.push(event);
    } else {
        trove_cats.value[index] = event;
    }
}

function updateFieldList(event: FieldProperties): void {
    const index = field_list.value.findIndex((field) => field.field_id === event.field_id);
    if (index === -1) {
        field_list.value.push(event);
    } else {
        field_list.value[index] = event;
    }
}

function updateProjectVisibility(event: ProjectVisibilityProperties): void {
    selected_visibility.value = event.new_visibility;
}

async function createProject(): Promise<void> {
    let project_properties: ProjectProperties = {
        shortname: name_properties.value.slugified_name,
        description: field_description.value,
        label: name_properties.value.name,
        is_public: !is_private.value,
        categories: trove_cats.value,
        fields: field_list.value,
    };

    project_properties = buildProjectPrivacy(
        root_store.selected_tuleap_template,
        root_store.selected_company_template,
        selected_visibility.value,
        project_properties,
    );

    if (root_store.selected_company_template?.id === "from_project_archive") {
        await root_store.createProjectFromArchive(project_properties, router);
        return;
    }

    await root_store.createProject(project_properties);
    if (!root_store.is_project_approval_required) {
        const params = new URLSearchParams();
        params.set("should-display-created-project-modal", "true");
        if (project_properties.xml_template_name) {
            params.set("xml-template-name", project_properties.xml_template_name);
        }

        redirectToUrl(
            "/projects/" + encodeURIComponent(name_properties.value.slugified_name) + "/?" + params,
        );
    } else {
        router.push("approval");
    }
}

function resetProjectCreationError(): void {
    root_store.resetProjectCreationError();
}
</script>
