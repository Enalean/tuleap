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
                <div class="tlp-form-element" data-test="register-new-project-information-form">
                    <label class="tlp-label" for="project-name">
                        <span v-translate>Name</span>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <input id="project-name" type="text" class="tlp-input" v-bind:placeholder="translated_placeholder"
                           disabled>
                    <p class="tlp-text-info"><i class="fa fa-life-saver register-new-project-icon"></i><span v-translate>Between 3 and 30 characters length</span>
                    </p>
                </div>
            </div>
        </div>
        <project-information-footer/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import UnderConstructionInformation from "../UnderConstructionInformation.vue";
import NewProjectBoxes from "../NewProjectBoxes.vue";
import ProjectInformationSvg from "./ProjectInformationSvg.vue";
import ProjectInformationFooter from "./ProjectInformationFooter.vue";

@Component({
    components: {
        ProjectInformationFooter,
        ProjectInformationSvg,
        NewProjectBoxes,
        UnderConstructionInformation
    }
})
export default class ProjectInformation extends Vue {
    get translated_placeholder(): string {
        return this.$gettext("My new project");
    }
}
</script>
