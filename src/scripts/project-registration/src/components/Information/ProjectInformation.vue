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
        <div class="tlp-alert-danger" v-if="has_error" data-test="project-creation-failed">
            {{ error }}
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
                            v-if="can_user_choose_project_visibility"
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
                            v-for="trovecat in trove_categories"
                            v-bind:key="trovecat.id"
                            v-bind:trovecat="trovecat"
                        />
                        <fields-list
                            v-for="field in project_fields"
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
    FieldData,
    FieldProperties,
    ProjectNameProperties,
    ProjectProperties,
    ProjectVisibilityProperties,
    TemplateData,
    TroveCatData,
    TroveCatProperties,
} from "../../type";
import { Getter, State, namespace } from "vuex-class";
import EventBus from "../../helpers/event-bus";
import TroveCategoryList from "./TroveCat/TroveCategoryList.vue";
import FieldDescription from "./Fields/FieldDescription.vue";
import PolicyAgreement from "./Agreement/PolicyAgreement.vue";
import FieldsList from "./Fields/FieldsList.vue";
import { redirectToUrl } from "../../helpers/location-helper";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED,
} from "../../constant";
const configuration = namespace("configuration");

const DEFAULT_PROJECT_ID = "100";

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
    @Getter
    has_error!: boolean;

    @Getter
    is_template_selected!: boolean;

    @State
    error!: string;

    @configuration.State
    are_restricted_users_allowed!: boolean;

    @configuration.State
    project_default_visibility!: string;

    @State
    selected_tuleap_template!: TemplateData;

    @configuration.State
    is_project_approval_required!: boolean;

    @configuration.State
    trove_categories!: Array<TroveCatData>;

    @configuration.State
    project_fields!: Array<FieldData>;

    @State
    selected_company_template!: TemplateData;

    @configuration.State
    can_user_choose_project_visibility!: boolean;

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
        if (!this.is_template_selected) {
            this.$router.push("new");
            return;
        }

        if (this.selected_tuleap_template) {
            this.selected_template_name = this.selected_tuleap_template.title;
        } else if (this.selected_company_template) {
            this.selected_template_name = this.selected_company_template.title;
        }

        this.selected_visibility = this.project_default_visibility;
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
        const project_properties: ProjectProperties = this.buildProjectPropertyDetailedPrivacy();

        await this.$store.dispatch("createProject", project_properties);

        if (!this.is_project_approval_required) {
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

    buildProjectPropertyDetailedPrivacy(): ProjectProperties {
        const project_properties: ProjectProperties = {
            shortname: this.name_properties.slugified_name,
            description: this.field_description,
            label: this.name_properties.name,
            is_public: !this.is_private,
            categories: this.trove_cats,
            fields: this.field_list,
        };

        if (
            this.selected_tuleap_template &&
            this.selected_tuleap_template.id !== DEFAULT_PROJECT_ID
        ) {
            project_properties.xml_template_name = this.selected_tuleap_template.id;
        }
        if (
            this.selected_tuleap_template &&
            this.selected_tuleap_template.id === DEFAULT_PROJECT_ID
        ) {
            project_properties.template_id = parseInt(this.selected_tuleap_template.id, 10);
        }
        if (this.selected_company_template) {
            project_properties.template_id = parseInt(this.selected_company_template.id, 10);
        }

        let is_public_project = null;
        let is_restricted_allowed_for_the_project = null;
        switch (this.selected_visibility) {
            case ACCESS_PUBLIC:
                is_public_project = true;
                is_restricted_allowed_for_the_project = false;
                break;
            case ACCESS_PRIVATE:
                is_public_project = false;
                is_restricted_allowed_for_the_project = true;
                break;
            case ACCESS_PUBLIC_UNRESTRICTED:
                is_public_project = true;
                is_restricted_allowed_for_the_project = true;
                break;
            case ACCESS_PRIVATE_WO_RESTRICTED:
                is_public_project = false;
                is_restricted_allowed_for_the_project = false;
                break;
            default:
                throw new Error("Unable to build the project privacy properties");
        }
        project_properties.is_public = is_public_project;
        project_properties.allow_restricted = is_restricted_allowed_for_the_project;
        return project_properties;
    }

    resetProjectCreationError(): void {
        this.$store.commit("resetProjectCreationError");
    }
}
</script>
