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
    <div>
        <div class="project-shortname-slugified-section"
             v-if="shouldDisplaySlug()"
             v-on:click="is_in_edit_mode = true"
             data-test="project-shortname-slugified-section"
        >
            <span v-translate>Project shortname:</span>
            <div class="project-shortname-slugified">{{ slugified_project_name }}</div>
            <i class="fa fa-pencil project-shortname-edit-icon"/>
        </div>
        <div class="tlp-form-element"
             v-bind:class="should_user_correct_shortname"
             data-test="project-shortname-edit-section">
            <label class="tlp-label" for="project-short-name"><span v-translate>Project shortname</span> <i class="fa fa-asterisk"/></label>
            <input type="text"
                   class="tlp-input"
                   id="project-short-name"
                   name="shortname"
                   ref="shortname"
                   v-bind:placeholder="$gettext('Project shortname')"
                   v-bind:minlength="min_project_length"
                   v-bind:maxlength="max_project_length"
                   v-bind:pattern="short_name_validation_pattern"
                   required
                   v-on:input="updateProjectShortName($refs.shortname.value)"
                   v-bind:value="slugified_project_name"
                   data-test="new-project-name"
            >
            <p class="tlp-text-danger" v-if="has_slug_error">
                <translate v-bind:translate-params="{min: min_project_length, max: max_project_length}">
                    Project short name must have between %{ min } and %{ max } characters length. It can only contain alphanumerical characters and dashes. Must start with a letter.
                </translate>
            </p>
            <p class="tlp-text-info">
                <i class="fa fa-life-saver register-new-project-icon"/>
                <span v-translate>Must start with a letter, avoid spaces and punctuation.</span>
            </p>
        </div>
    </div>
</template>
<script lang="ts">
import EventBus from "../../../helpers/event-bus";
import slugify from "slugify";
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { Getter } from "vuex-class";

@Component
export default class ProjectName extends Vue {
    @Getter
    has_error!: boolean;

    $refs!: {
        shortname: HTMLFormElement;
    };

    project_name = "";
    slugified_project_name = "";
    has_slug_error = false;
    is_in_edit_mode = false;

    min_project_length = 3;
    max_project_length = 30;

    get short_name_validation_pattern(): string {
        const invalid_length = this.min_project_length - 1;
        return `^[a-zA-Z][a-zA-Z0-9/-]{${invalid_length},${this.max_project_length}}`;
    }

    get should_user_correct_shortname(): string {
        if (this.shouldDisplayEditShortName()) {
            return "project-short-name-edit-section";
        }

        return "project-short-name-hidden-section";
    }

    mounted(): void {
        EventBus.$on("slugify-project-name", this.slugifyProjectShortName);
    }

    beforeDestroy(): void {
        EventBus.$off("slugify-project-name", this.slugifyProjectShortName);
    }

    slugifyProjectShortName(value: string): void {
        if (this.is_in_edit_mode) {
            return;
        }

        this.has_slug_error = false;
        this.project_name = value;

        this.slugified_project_name = slugify(value);
        if (!this.$refs.shortname.checkValidity()) {
            this.has_slug_error = true;
        }

        this.$refs.shortname.value = this.slugified_project_name;

        EventBus.$emit("update-project-name", {
            slugified_name: this.slugified_project_name,
            name: this.project_name
        });
    }

    updateProjectShortName(value: string): void {
        this.has_slug_error = !this.$refs.shortname.checkValidity();
        this.slugified_project_name = value;

        EventBus.$emit("update-project-name", {
            slugified_name: value,
            name: this.project_name
        });
    }

    shouldDisplaySlug(): boolean {
        if (this.slugified_project_name.length === 0 || this.is_in_edit_mode) {
            return false;
        }

        return !this.has_error;
    }

    shouldDisplayEditShortName(): boolean {
        if (this.is_in_edit_mode) {
            return true;
        }

        return this.has_error;
    }
}
</script>
