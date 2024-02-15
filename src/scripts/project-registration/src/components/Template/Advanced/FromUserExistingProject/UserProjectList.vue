<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <translate v-if="projectList.length === 0" data-test="no-project-list">
            You are not administrator of any project.
        </translate>
        <select
            class="tlp-select tlp-select-adjusted user-project-list-select"
            id="from-another-project"
            data-test="from-another-project"
            name="from-another-project"
            v-model="selected_project"
            v-on:change="storeSelectedTemplate()"
            v-else
        >
            <option disabled value=""><translate>Please choose a project...</translate></option>
            <option
                v-for="project in projectList"
                v-bind:value="project"
                v-bind:key="project.id"
                v-bind:data-test="`select-project-${project.id}`"
            >
                {{ project.title }}
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import { Component, Prop, Watch } from "vue-property-decorator";
import Vue from "vue";
import type { TemplateData } from "../../../../type";

@Component({})
export default class UserProjectList extends Vue {
    @Prop({ required: true })
    readonly projectList!: Array<TemplateData>;

    @Prop({ required: true })
    readonly selectedCompanyTemplate!: null | TemplateData;

    @Watch("selectedCompanyTemplate")
    observeSelectedCompanyTemplate(): void {
        if (this.selectedCompanyTemplate === null) {
            this.selected_project = "";
        }
    }

    selected_project: TemplateData | string = "";

    mounted(): void {
        if (this.selectedCompanyTemplate !== null) {
            this.selected_project = this.selectedCompanyTemplate;
        }
    }

    storeSelectedTemplate(): void {
        this.$store.dispatch("setSelectedTemplate", this.selected_project);
    }
}
</script>
