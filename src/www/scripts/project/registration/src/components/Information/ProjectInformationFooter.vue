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
        <hr>
        <div class="project-registration-button-container">
            <router-link to="/new"
                         v-on:click.native="resetSelectedTemplate"
                         class="project-registration-back-button"
                         data-test="project-registration-back-button">
                <i class="fa fa-long-arrow-left"/>
                <span v-translate>Back</span>
            </router-link>
            <button type="button"
                    class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                    data-test="project-registration-next-button"
                    v-on:click="createProject"
            >
                <span v-translate>Start my project</span> <i v-bind:class="get_icon" data-test="project-submission-icon"/>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ProjectNameProperties, ProjectProperties, TemplateData } from "../../type";
import { Getter, State } from "vuex-class";
import { redirectToUrl } from "../../helpers/location-helper";
import {
    ACCESS_PRIVATE,
    ACCESS_PRIVATE_WO_RESTRICTED,
    ACCESS_PUBLIC,
    ACCESS_PUBLIC_UNRESTRICTED
} from "../../constant";

@Component
export default class ProjectInformationFooter extends Vue {
    @State
    selected_template!: TemplateData;

    @State
    is_creating_project!: boolean;

    @Getter
    has_error!: boolean;

    @Prop({ required: true })
    readonly project_name_properties!: ProjectNameProperties;

    @Prop({ required: true })
    readonly is_public!: boolean;

    @Prop({ required: true })
    readonly privacy!: string;

    @State
    are_restricted_users_allowed!: boolean;

    is_loading = false;

    get get_icon(): string {
        if (!this.is_creating_project) {
            return "fa tlp-button-icon-right fa-arrow-circle-o-right";
        }

        return "fa tlp-button-icon-right fa-spin fa-circle-o-notch";
    }

    resetSelectedTemplate(): void {
        this.$store.dispatch("setSelectedTemplate", null);
    }

    async createProject(): Promise<void> {
        const project_properties: ProjectProperties = this.buildProjectPropertyDetailedPrivacy();

        await this.$store.dispatch("createProject", project_properties);

        redirectToUrl("/my");
    }

    buildProjectPropertyDetailedPrivacy(): ProjectProperties {
        if (!this.are_restricted_users_allowed) {
            return {
                shortname: this.project_name_properties.slugified_name,
                label: this.project_name_properties.name,
                is_public: this.is_public,
                xml_template_name: this.selected_template.name
            };
        }

        let is_public_project = null;
        let is_restricted_allowed_for_the_project = null;
        switch (this.privacy) {
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
            shortname: this.project_name_properties.slugified_name,
            label: this.project_name_properties.name,
            is_public: is_public_project,
            allow_restricted: is_restricted_allowed_for_the_project,
            xml_template_name: this.selected_template.name
        };
    }
}
</script>
