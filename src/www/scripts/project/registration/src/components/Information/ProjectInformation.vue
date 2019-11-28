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
        <div class="register-new-project-section">
            <project-information-svg/>
            <div class="register-new-project-list">
                <h2>
                    <span class="tlp-badge-primary register-new-project-section-badge">2</span>
                    <span v-translate>Project information</span>
                </h2>
                <under-construction-information/>
                <div class="register-new-project-information-form-container" data-test="register-new-project-information-form">
                    <project-name v-model="name_properties"/>
                    <project-information-input-privacy-switch v-model="is_private"/>
                </div>
            </div>
        </div>
        <project-information-footer
            v-bind:project_name_properties="name_properties"
            v-bind:is_public="is_private === false"
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
import { ProjectNameProperties } from "../../type";

@Component({
    components: {
        ProjectName,
        ProjectInformationInputPrivacySwitch,
        ProjectInformationFooter,
        ProjectInformationSvg,
        UnderConstructionInformation
    }
})
export default class ProjectInformation extends Vue {
    name_properties: ProjectNameProperties = {
        slugified_name: "",
        name: "",
        is_valid: false
    };

    is_private = false;
}
</script>
