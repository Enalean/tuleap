<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -->

<template>
    <form
        role="dialog"
        aria-labelledby="select-gitlab-project-modal-title"
        id="select-gitlab-projects-modal"
        ref="select_modal"
        v-on:submit="fetchProjects"
        data-test="select-gitlab-project-modal-form"
    >
        <div class="tlp-modal-body git-repository-create-modal-body">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th></th>
                        <th colspan="2"><translate>Project</translate></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="projects.length === 0">
                        <td
                            colspan="3"
                            class="tlp-table-cell-empty"
                            data-test="gitlab-empty-projects"
                        >
                            <translate>No project is available with your GitLab account</translate>
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="project of projects"
                        v-bind:key="project.id"
                        v-bind:data-test="`gitlab-projects-displayed-${project.id}`"
                    >
                        <td class="gitlab-select-radio-button-container">
                            <label class="tlp-radio">
                                <input
                                    type="radio"
                                    v-bind:id="project.id"
                                    v-bind:value="project"
                                    v-model="selected_project"
                                    class="gitlab-select-radio-button"
                                />
                            </label>
                        </td>
                        <td class="gitlab-select-avatar">
                            <img
                                v-if="project.avatar_url !== null"
                                v-bind:src="project.avatar_url"
                                v-bind:alt="project.name_with_namespace"
                                class="gitlab-avatar"
                            />
                        </td>
                        <td>
                            {{ project.name_with_namespace }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-test="gitlab-button-back"
                v-on:click="$emit('to-back-button')"
            >
                <i class="fa fa-arrow-left tlp-button-icon" data-test="icon-spin"></i>
                <translate>Back</translate>
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="disabled_button"
                data-test="button_integrate_gitlab_project"
            >
                <i class="fa fa-arrow-right tlp-button-icon" data-test="icon-spin"></i>
                <translate>Integrate the project</translate>
            </button>
        </div>
    </form>
</template>

<script>
export default {
    name: "ListProjectsModal",
    props: {
        projects: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            selected_project: null,
            is_loading: false,
        };
    },
    computed: {
        disabled_button() {
            return this.selected_project === null || this.is_loading;
        },
    },
    methods: {
        fetchProjects(event) {
            event.preventDefault();
        },
    },
};
</script>
