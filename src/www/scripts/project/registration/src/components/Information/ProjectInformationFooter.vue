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
                <i class="fa fa-long-arrow-left"></i>
                <span v-translate>Back</span>
            </router-link>
            <button type="button"
                    class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                    data-test="project-registration-next-button"
                    v-on:click="createProject"
                    v-bind:disabled="! project_name_properties.is_valid"
            >
                <span v-translate>Start my project</span> <i v-bind:class="getIcon"/>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { ProjectProperties, TemplateData, ProjectNameProperties } from "../../type";
import { State } from "vuex-class";
import { redirectToUrl } from "../../helpers/location-helper";

@Component
export default class ProjectInformationFooter extends Vue {
    @State
    selected_template!: TemplateData;

    @Prop({ required: true })
    readonly project_name_properties!: ProjectNameProperties;

    @Prop({ required: true })
    readonly is_public!: boolean;

    is_loading = false;

    get getIcon(): string {
        if (!this.is_loading) {
            return "fa tlp-button-icon-right fa-arrow-circle-o-right";
        }

        return "fa tlp-button-icon-right fa-spin fa-circle-o-notch";
    }

    resetSelectedTemplate(): void {
        this.$store.dispatch("setSelectedTemplate", null);
    }

    async createProject(): Promise<void> {
        const project_properties: ProjectProperties = {
            shortname: this.project_name_properties.slugified_name,
            label: this.project_name_properties.name,
            is_public: this.is_public,
            allow_restricted: true,
            xml_template_name: this.selected_template.name
        };

        this.is_loading = true;

        await this.$store.dispatch("createProject", project_properties);

        this.is_loading = false;

        redirectToUrl("/my");
    }
}
</script>
