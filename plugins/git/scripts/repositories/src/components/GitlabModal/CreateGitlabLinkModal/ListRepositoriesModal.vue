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
        aria-labelledby="select-gitlab-repository-modal-title"
        id="select-gitlab-repositories-modal"
        ref="select_modal"
        v-on:submit="fetchRepositories"
        data-test="select-gitlab-repository-modal-form"
    >
        <div class="tlp-modal-body git-repository-create-modal-body">
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th></th>
                        <th colspan="2"><translate>Repository</translate></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="repository of repositories"
                        v-bind:key="repository.id"
                        v-bind:data-test="`gitlab-repositories-displayed-${repository.id}`"
                    >
                        <td class="gitlab-select-radio-button-container">
                            <label class="tlp-radio">
                                <input
                                    type="radio"
                                    v-bind:id="repository.id"
                                    v-bind:value="repository"
                                    v-model="selected_repository"
                                    class="gitlab-select-radio-button"
                                />
                            </label>
                        </td>
                        <td class="gitlab-select-avatar">
                            <img
                                v-if="repository.avatar_url !== null"
                                v-bind:src="repository.avatar_url"
                                v-bind:alt="repository.name_with_namespace"
                                class="gitlab-avatar"
                            />
                        </td>
                        <td>
                            {{ repository.name_with_namespace }}
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
                data-test="button-integrate-gitlab-repository"
            >
                <i class="fa fa-arrow-right tlp-button-icon" data-test="icon-spin"></i>
                <translate>Integrate the repository</translate>
            </button>
        </div>
    </form>
</template>

<script>
export default {
    name: "ListRepositoriesModal",
    props: {
        repositories: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            selected_repository: null,
            is_loading: false,
        };
    },
    computed: {
        disabled_button() {
            return this.selected_repository === null || this.is_loading;
        },
    },
    methods: {
        fetchRepositories(event) {
            event.preventDefault();
        },
        reset() {
            this.selected_repository = null;
            this.is_loading = false;
        },
    },
};
</script>
