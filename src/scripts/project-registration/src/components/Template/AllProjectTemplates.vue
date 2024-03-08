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
    <div class="project-registration-content">
        <div class="register-new-project-section">
            <new-project-boxes />
            <div class="register-new-project-list">
                <h1 class="project-registration-title" v-translate>Start a new project</h1>

                <nav class="tlp-wizard">
                    <span class="tlp-wizard-step-current" v-translate>Template</span>
                    <span class="tlp-wizard-step-next" v-translate>Information</span>
                </nav>

                <h2>
                    <span v-translate>What kind of project is it?</span>
                </h2>

                <p v-translate>
                    The project template you will select provides pre-configured tools. Don’t worry,
                    you can always modify your project workspace after it has been created.
                </p>

                <template-selection />
                <template-footer />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import NewProjectBoxes from "./NewProjectBoxesSvg.vue";
import TemplateFooter from "./TemplateFooter.vue";
import TemplateSelection from "./TemplateSelection.vue";
</script>
