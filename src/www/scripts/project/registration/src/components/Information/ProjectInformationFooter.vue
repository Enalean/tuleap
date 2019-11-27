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
        <hr>
        <div class="project-registration-button-container">
            <router-link to="/new"
                         v-on:click.native="resetSelectedTemplate"
                         class="project-registration-back-button"
                         data-test="project-registration-back-button">
                <i class="fa fa-long-arrow-left"></i>
                <span v-translate>Back</span>
            </router-link>
            <button type="button"
                    class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                    data-test="project-registration-next-button"
                    disabled
            >
                <span v-translate>Start my project</span> <i class="fa fa-arrow-circle-o-right tlp-button-icon-right"></i>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";

@Component
export default class ProjectInformationFooter extends Vue {
    resetSelectedTemplate(): void {
        this.$store.dispatch("setSelectedTemplate", null);
    }
}
</script>
