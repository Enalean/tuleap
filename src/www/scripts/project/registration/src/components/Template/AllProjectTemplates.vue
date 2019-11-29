<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div>
        <h1 class="project-registration-title" v-translate>Start a new project</h1>

        <div class="register-new-project-section">
            <new-project-boxes/>
            <div class="register-new-project-list">
                <h2>
                    <span class="tlp-badge-primary register-new-project-section-badge">1</span>
                    <span v-translate>What kind of project is it?</span>
                </h2>

                <p v-translate>
                    The project template you will select provides some services. Don’t worry, you can always modify your project workspace after your project has been created.
                </p>

                <under-construction-information/>
                <tuleap-template-list/>
            </div>
        </div>
        <template-footer/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import UnderConstructionInformation from "../UnderConstructionInformation.vue";
import NewProjectBoxes from "./NewProjectBoxesSvg.vue";
import TemplateFooter from "./TemplateFooter.vue";

@Component({
    components: {
        TemplateFooter,
        NewProjectBoxes,
        UnderConstructionInformation,
        TuleapTemplateList
    }
})
export default class ProjectList extends Vue {}
</script>
