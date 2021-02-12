<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <breadcrumb
            v-bind:project_public_name="projectPublicName()"
            v-bind:project_short_name="projectShortName()"
            v-bind:project_privacy="projectPrivacy()"
            v-bind:project_flags="projectFlags()"
        />
        <h1 class="program-management-title-header" v-translate>Backlog</h1>
        <div class="program-backlog" data-test="backlog-section">
            <to-be-planned class="to-be-planned" />
            <program-increment-list class="program-increment" />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import Breadcrumb from "./Breadcrumb.vue";
import {
    getProjectPublicName,
    projectShortName,
    projectPrivacy,
    projectFlags,
} from "../configuration";
import ProgramIncrementList from "./Backlog/ProgramIncrement/ProgramIncrementList.vue";
import ToBePlanned from "./Backlog/ToBePlanned/ToBePlanned.vue";
import type { ProjectFlag, ProjectPrivacy } from "@tuleap/vue-breadcrumb-privacy";

@Component({
    components: { ToBePlanned, ProgramIncrementList: ProgramIncrementList, Breadcrumb },
})
export default class App extends Vue {
    public projectPublicName(): string {
        return getProjectPublicName();
    }
    public projectShortName(): string {
        return projectShortName();
    }
    public projectPrivacy(): ProjectPrivacy {
        return projectPrivacy();
    }
    public projectFlags(): Array<ProjectFlag> {
        return projectFlags();
    }
}
</script>
