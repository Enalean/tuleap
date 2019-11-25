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
    <div class="tlp-form-element">
        <label class="tlp-label" for="project-name" v-translate>Name <i class="fa fa-asterisk"></i></label>
        <input id="project-name"
               type="text"
               class="tlp-input"
               data-test="new-project-name"
               v-bind:placeholder="translated_placeholder"
               minlength="3"
               maxlength="30"
               pattern="^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ0-9/ /-]{3,30}"
               ref="name"
               v-on:input="slugifiedProjectName()"
               required
        >
        <p class="tlp-text-info"><i class="fa fa-life-saver register-new-project-icon"></i><span v-translate>Between 3 and 30 characters length</span></p>

        <div class="project-shortname-slugified-section" v-if="slugified_project_name !== ''">
            <span v-translate>Project shortname:</span>
            <div class="project-shortname-slugified">{{ slugified_project_name }}</div>
        </div>

        <p class="tlp-text-danger" v-if="error.length > 0">{{ error }}</p>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import slugify from "slugify";
import { Component } from "vue-property-decorator";

@Component({})
export default class ProjectName extends Vue {
    get translated_placeholder(): string {
        return this.$gettext("My new project");
    }

    $refs!: {
        name: HTMLFormElement;
    };

    slugified_project_name = "";
    error = "";

    slugifiedProjectName(): void {
        this.slugified_project_name = slugify(this.$refs.name.value);

        if (!this.$refs.name.checkValidity()) {
            this.error = this.$gettext(
                "Project short name must have between 3 and 30 characters length. It can only contains alphanumerical characters and dashes. Must start with a letter."
            );
            return;
        }

        this.error = "";
    }
}
</script>
