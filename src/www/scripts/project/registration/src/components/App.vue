<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="project-registration">
        <h1 class="project-registration-title" v-translate>Start a new project</h1>

        <div class="register-new-project-section">
            <new-project-boxes/>
            <project-list/>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import NewProjectBoxes from "./NewProjectBoxes.vue";
import ProjectList from "./ProjectList.vue";

@Component({
    components: { ProjectList, NewProjectBoxes }
})
export default class App extends Vue {}
</script>
