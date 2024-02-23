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
    <div class="project-registration-button-container">
        <div class="project-registration-content">
            <div>
                <router-link
                    to="/new"
                    v-on:click.native="resetProjectCreationError"
                    class="project-registration-back-button"
                    data-test="project-registration-back-button"
                >
                    <i class="fas fa-long-arrow-alt-left"></i>
                    <span class="project-registration-back-button-text" v-translate>Back</span>
                </router-link>
                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                    data-test="project-registration-next-button"
                    v-bind:disabled="root_store.is_creating_project"
                >
                    <span v-translate>Start my project</span>
                    <i v-bind:class="get_icon" data-test="project-submission-icon" />
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { useStore } from "../../stores/root";

@Component({})
export default class ProjectInformationFooter extends Vue {
    root_store = useStore();

    is_loading = false;

    get get_icon(): string {
        if (!this.root_store.is_creating_project) {
            return "fa tlp-button-icon-right fa-arrow-circle-o-right";
        }

        return "fa tlp-button-icon-right fa-spin fa-circle-o-notch";
    }

    resetProjectCreationError(): void {
        this.root_store.resetProjectCreationError();
    }
}
</script>
