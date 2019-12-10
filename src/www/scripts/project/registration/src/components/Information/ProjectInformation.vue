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
        <div class="tlp-alert-danger" v-if="has_error" data-test="project-creation-failed">{{ error }}</div>

        <h1 class="project-registration-title" v-translate>Start a new project</h1>

        <form v-on:submit.prevent="createProject" data-test="project-registration-form">
            <div class="register-new-project-section">
                <project-information-svg/>
                <div class="register-new-project-list">
                    <h2>
                        <span class="tlp-badge-primary register-new-project-section-badge">2</span>
                        <span v-translate>Project information</span>
                    </h2>
                    <under-construction-information/>

                    <div class="register-new-project-information-form-container"
                         v-bind:class="{'register-new-project-information-form-container-restricted-allowed' : are_restricted_users_allowed}"
                         data-test="register-new-project-information-form"
                    >
                        <project-name v-model="name_properties"/>
                        <project-information-input-privacy-list v-if="are_restricted_users_allowed"
                                                                v-model="selected_visibility"
                                                                data-test="register-new-project-information-list"
                        />
                        <project-information-input-privacy-switch v-else
                                                                  v-model="is_private"
                                                                  data-test="register-new-project-information-switch"
                        />
                    </div>
                    <field-description v-model="field_description"/>
                    <trove-category-list v-model="trove_cats"
                                         v-for="trovecat in trove_categories"
                                         v-bind:key="trovecat.id"
                                         v-bind:trovecat="trovecat"
                    />
                    <fields-list v-for="field in project_fields"
                                 v-bind:key="field.group_desc_id + field.desc_name"
                                 v-bind:field="field"

                    />
                    <policy-agreement/>
                </div>
            </div>
            <project-information-footer/>
        </form>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import UnderConstructionInformation from "../UnderConstructionInformation.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";
import ProjectName from "./Input/ProjectName.vue";
import ProjectInformationInputPrivacySwitch from "./Input/ProjectInformationInputPrivacySwitch.vue";
import ProjectInformationInputPrivacyList from "./Input/ProjectInformationInputPrivacyList.vue";
import {
    ProjectNameProperties,
    TroveCatProperties,
    TemplateData,
    ProjectProperties,
    FieldProperties,
    TroveCatData,
    FieldData
} from "../../type";
import { Getter, State } from "vuex-class";
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
    ACCESS_PUBLIC_UNRESTRICTED
} from "../../constant";

@Component({
    components: {
        PolicyAgreement,
        FieldDescription,
        FieldsList,
        TroveCategoryList,
        ProjectInformationInputPrivacyList,
        ProjectName,
        ProjectInformationInputPrivacySwitch,
        ProjectInformationFooter,
        ProjectInformationSvg,
        UnderConstructionInformation
    }
})
export default class ProjectInformation extends Vue {
    @Getter
    has_error!: boolean;

    @State
    error!: string;

    @State
    are_restricted_users_allowed!: boolean;

    @State
    project_default_visibility!: string;

    @State
    selected_template!: TemplateData;

    @State
    is_project_approval_required!: boolean;

    @State
    trove_categories!: Array<TroveCatData>;

    @State
    project_fields!: Array<FieldData>;

    selected_visibility = "public";

    name_properties: ProjectNameProperties = {
        slugified_name: "",
        name: ""
    };

    field_description = "";

    trove_cats: Array<TroveCatProperties> = [];

    is_private = false;

    field_list: Array<FieldProperties> = [];

    mounted(): void {
        if (!this.selected_template) {
            this.$router.push("new");
            return;
        }

        this.selected_visibility = this.project_default_visibility;
        EventBus.$on("update-project-name", this.updateProjectName);
        EventBus.$on("choose-trove-cat", this.updateTroveCat);
        EventBus.$on("update-field-list", this.updateFieldList);
    }

    beforeDestroy(): void {
        EventBus.$off("update-project-name", this.updateProjectName);
        EventBus.$off("choose-trove-cat", this.updateTroveCat);
        EventBus.$off("update-field-list", this.updateFieldList);
    }

    updateProjectName(event: ProjectNameProperties): void {
        this.name_properties = event;
    }

    updateTroveCat(event: TroveCatProperties): void {
        const index = this.trove_cats.findIndex(trove => trove.category_id === event.category_id);
        if (index === -1) {
            this.trove_cats.push(event);
        } else {
            this.trove_cats[index] = event;
        }
    }

    updateFieldList(event: FieldProperties): void {
        const index = this.field_list.findIndex(field => field.field_id === event.field_id);
        if (index === -1) {
            this.field_list.push(event);
        } else {
            this.field_list[index] = event;
        }
    }

    async createProject(): Promise<void> {
        const project_properties: ProjectProperties = this.buildProjectPropertyDetailedPrivacy();

        await this.$store.dispatch("createProject", project_properties);

        if (!this.is_project_approval_required) {
            redirectToUrl(
                "/projects/" +
                    encodeURIComponent(this.name_properties.slugified_name) +
                    "/?should-display-created-project-modal=true"
            );
        } else {
            this.$router.push("approval");
        }
    }

    buildProjectPropertyDetailedPrivacy(): ProjectProperties {
        if (!this.are_restricted_users_allowed) {
            return {
                shortname: this.name_properties.slugified_name,
                description: this.field_description,
                label: this.name_properties.name,
                is_public: !this.is_private,
                xml_template_name: this.selected_template.name,
                categories: this.trove_cats,
                fields: this.field_list
            };
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

        return {
            shortname: this.name_properties.slugified_name,
            description: this.field_description,
            label: this.name_properties.name,
            is_public: is_public_project,
            allow_restricted: is_restricted_allowed_for_the_project,
            xml_template_name: this.selected_template.name,
            categories: this.trove_cats,
            fields: this.field_list
        };
    }
}
</script>
