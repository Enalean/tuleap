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
                        <h1 class="project-registration-title" v-translate>Start a new project</h1>

                        <nav class="tlp-wizard">
                            <router-link
                                v-bind:to="{ name: 'template' }"
                                v-on:click.native="resetProjectCreationError"
                                class="tlp-wizard-step-previous"
                                v-translate
                            >
                                Template
                            </router-link>
                            <span class="tlp-wizard-step-current" v-translate>Information</span>
                        </nav>

                        <h2>
                            <span v-translate>Project information</span>
                        </h2>
                        <div
                            class="register-new-project-information-form-container"
                            data-test="register-new-project-information-form"
                        ></div>
                        <div class="tlp-property">
                            <label class="tlp-label" v-translate>Chosen template</label>
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
                                <span v-translate>Visibility</span>
                                <i class="fa fa-asterisk"></i>
                            </label>
                            <project-information-input-privacy-list
                                data-test="register-new-project-information-list"
                            />
                        </div>

                        <field-description v-model="field_description" />
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

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
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
import EventBus from "../../helpers/event-bus";
import TroveCategoryList from "./TroveCat/TroveCategoryList.vue";
import FieldDescription from "./Fields/FieldDescription.vue";
import PolicyAgreement from "./Agreement/PolicyAgreement.vue";
import FieldsList from "./Fields/FieldsList.vue";
import { redirectToUrl } from "../../helpers/location-helper";
import { buildProjectPrivacy } from "../../helpers/privacy-builder";
import { useStore } from "../../stores/root";

@Component({
    components: {
        PolicyAgreement,
        FieldDescription,
        FieldsList,
        TroveCategoryList,
        ProjectInformationInputPrivacyList,
        ProjectName,
        ProjectInformationFooter,
        ProjectInformationSvg,
    },
})
export default class ProjectInformation extends Vue {
    root_store = useStore();

    selected_visibility = "";

    name_properties: ProjectNameProperties = {
        slugified_name: "",
        name: "",
    };

    field_description = "";

    trove_cats: Array<TroveCatProperties> = [];

    is_private = false;

    selected_template_name = "";

    field_list: Array<FieldProperties> = [];

    mounted(): void {
        if (!this.root_store.is_template_selected) {
            this.$router.push("new");
            return;
        }

        if (this.root_store.selected_tuleap_template) {
            this.selected_template_name = this.root_store.selected_tuleap_template.title;
        } else if (this.root_store.selected_company_template) {
            this.selected_template_name = this.root_store.selected_company_template.title;
        }

        this.selected_visibility = this.root_store.project_default_visibility;
        EventBus.$on("update-project-name", this.updateProjectName);
        EventBus.$on("choose-trove-cat", this.updateTroveCat);
        EventBus.$on("update-field-list", this.updateFieldList);
        EventBus.$on("update-project-visibility", this.updateProjectVisibility);
    }

    beforeDestroy(): void {
        EventBus.$off("update-project-name", this.updateProjectName);
        EventBus.$off("choose-trove-cat", this.updateTroveCat);
        EventBus.$off("update-field-list", this.updateFieldList);
        EventBus.$off("update-project-visibility", this.updateProjectVisibility);
    }

    updateProjectName(event: ProjectNameProperties): void {
        this.name_properties = event;
    }

    updateTroveCat(event: TroveCatProperties): void {
        const index = this.trove_cats.findIndex((trove) => trove.category_id === event.category_id);
        if (index === -1) {
            this.trove_cats.push(event);
        } else {
            this.trove_cats[index] = event;
        }
    }

    updateFieldList(event: FieldProperties): void {
        const index = this.field_list.findIndex((field) => field.field_id === event.field_id);
        if (index === -1) {
            this.field_list.push(event);
        } else {
            this.field_list[index] = event;
        }
    }

    updateProjectVisibility(event: ProjectVisibilityProperties): void {
        this.selected_visibility = event.new_visibility;
    }

    async createProject(): Promise<void> {
        let project_properties: ProjectProperties = {
            shortname: this.name_properties.slugified_name,
            description: this.field_description,
            label: this.name_properties.name,
            is_public: !this.is_private,
            categories: this.trove_cats,
            fields: this.field_list,
        };

        project_properties = buildProjectPrivacy(
            this.root_store.selected_tuleap_template,
            this.root_store.selected_company_template,
            this.selected_visibility,
            project_properties,
        );

        if (this.root_store.selected_company_template?.id === "from_project_archive") {
            await this.root_store.createProjectFromArchive(project_properties, this.$router);
            return;
        }

        await this.root_store.createProject(project_properties);
        if (!this.root_store.is_project_approval_required) {
            const params = new URLSearchParams();
            params.set("should-display-created-project-modal", "true");
            if (project_properties.xml_template_name) {
                params.set("xml-template-name", project_properties.xml_template_name);
            }

            redirectToUrl(
                "/projects/" +
                    encodeURIComponent(this.name_properties.slugified_name) +
                    "/?" +
                    params,
            );
        } else {
            this.$router.push("approval");
        }
    }

    resetProjectCreationError(): void {
        this.root_store.resetProjectCreationError();
    }
}
</script>
