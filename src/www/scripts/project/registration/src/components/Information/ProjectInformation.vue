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
                <trove-category-list v-model="trove_cats"/>
            </div>
        </div>

        <project-information-footer
            v-bind:project_name_properties="name_properties"
            v-bind:is_public="is_private === false"
            v-bind:privacy="selected_visibility"
            v-bind:trove_cats="trove_cats"
            v-bind:field_description="field_description"
        />
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
import { ProjectNameProperties, TroveCatProperties, TemplateData } from "../../type";
import { Getter, State } from "vuex-class";
import EventBus from "../../helpers/event-bus";
import TroveCategoryList from "./TroveCat/TroveCategoryList.vue";
import FieldDescription from "../Field/FieldDescription.vue";

@Component({
    components: {
        FieldDescription,
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

    selected_visibility = "public";

    name_properties: ProjectNameProperties = {
        slugified_name: "",
        name: ""
    };

    field_description = "";

    trove_cats: Array<TroveCatProperties> = [];

    is_private = false;

    mounted(): void {
        if (!this.selected_template) {
            this.$router.push("new");
            return;
        }

        this.selected_visibility = this.project_default_visibility;
        EventBus.$on("update-project-name", this.updateProjectName);
        EventBus.$on("choose-trove-cat", this.updateTroveCat);
    }

    beforeDestroy(): void {
        EventBus.$off("update-project-name", this.updateProjectName);
        EventBus.$off("choose-trove-cat", this.updateTroveCat);
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
}
</script>
