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
    <section
        class="project-registration-default-templates-section"
        data-test="project-load-user-project-list"
    >
        <div class="project-registration-template-card">
            <input
                type="radio"
                v-bind:id="'project-registration-tuleap-template-other-user-project'"
                v-bind:checked="is_checked"
                class="project-registration-selected-template"
                name="selected-template"
                data-test="selected-template-input"
            />

            <label
                class="tlp-card tlp-card-selectable project-registration-template-label"
                data-test="project-registration-card-label"
                v-bind:for="'project-registration-tuleap-template-other-user-project'"
                v-on:click="loadUserProjects"
            >
                <div class="project-registration-template-glyph"><svg-template /></div>
                <div class="project-registration-template-content">
                    <h4 class="project-registration-template-card-title" v-translate>
                        From another project I'm admin of
                    </h4>
                    <div
                        v-if="!should_choose_a_project && !is_loading_project_list && !has_error"
                        class="project-registration-template-card-description"
                        data-test="user-project-description"
                    >
                        <translate>
                            Project configuration will be duplicated into your new project.
                        </translate>
                    </div>
                    <div
                        v-else-if="is_loading_project_list && !has_error"
                        data-test="user-project-spinner"
                    >
                        <i class="fa fa-spinner fa-spin fa-circle-o-notch"></i>
                    </div>
                    <div
                        v-else-if="has_error"
                        class="tlp-text-danger"
                        data-test="user-project-error"
                    >
                        <translate>Oh snap! Failed to load project you are admin of.</translate>
                    </div>
                    <user-project-list
                        v-else
                        v-bind:project-list="projects_user_is_admin_of"
                        v-bind:selected-company-template="selected_company_template"
                        data-test="user-project-list"
                    />
                </div>
            </label>
        </div>
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { State } from "vuex-class";
import type { TemplateData } from "../../../../type";
import TemplateCard from "../../TemplateCard.vue";
import SvgTemplate from "./SvgTemplate.vue";
import UserProjectList from "./UserProjectList.vue";

@Component({
    components: {
        TemplateCard,
        SvgTemplate,
        UserProjectList,
    },
})
export default class FromExistingUserProjectTemplateCard extends Vue {
    should_choose_a_project = false;
    is_loading_project_list = false;
    has_error = false;

    @State
    selected_company_template!: null | TemplateData;

    @State
    projects_user_is_admin_of!: TemplateData[];

    mounted(): void {
        if (this.projects_user_is_admin_of.length > 0) {
            this.should_choose_a_project = this.is_checked;
        }
    }

    async loadUserProjects(): Promise<void> {
        if (this.projects_user_is_admin_of.length > 0) {
            this.is_loading_project_list = false;
            this.should_choose_a_project = true;
            return;
        }

        this.has_error = false;
        this.is_loading_project_list = true;
        try {
            await this.$store.dispatch("loadUserProjects");
        } catch (error) {
            this.has_error = true;
            throw error;
        } finally {
            this.is_loading_project_list = false;
            this.should_choose_a_project = true;
        }
    }

    get is_checked(): boolean {
        return (
            this.projects_user_is_admin_of.findIndex(
                (project) => project.id === this.selected_company_template?.id,
            ) !== -1
        );
    }
}
</script>
