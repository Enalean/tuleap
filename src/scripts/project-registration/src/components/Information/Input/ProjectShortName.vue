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
        <div
            class="project-shortname-slugified-section"
            v-if="shouldDisplaySlug()"
            v-on:click="is_in_edit_mode = true"
            data-test="project-shortname-slugified-section"
        >
            ↳&nbsp;
            <span v-translate>Project shortname:</span>
            <div class="project-shortname-slugified">{{ slugified_project_name }}</div>
            <i class="fas fa-pencil-alt project-shortname-edit-icon"></i>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="[
                has_slug_error ? 'tlp-form-element-error' : '',
                should_user_correct_shortname,
            ]"
            data-test="project-shortname-edit-section"
        >
            <label class="tlp-label" for="project-short-name">
                <span v-translate>Project shortname</span>
                <i class="fa fa-asterisk" />
            </label>
            <input
                type="text"
                class="tlp-input tlp-input-large"
                id="project-short-name"
                name="shortname"
                ref="shortname"
                v-bind:placeholder="$gettext('project-shortname')"
                v-bind:minlength="min_project_length"
                v-bind:maxlength="max_project_length"
                v-on:input="updateProjectShortName($refs.shortname.value)"
                v-bind:value="slugified_project_name"
                data-test="new-project-shortname"
            />
            <p class="tlp-text-info">
                <i class="far fa-fw fa-life-ring"></i>
                <span v-translate>Must start with a letter, without spaces nor punctuation.</span>
            </p>
            <p class="tlp-text-danger" v-if="has_slug_error">
                <i class="fa fa-fw fa-exclamation-circle"></i>
                <translate
                    v-bind:translate-params="{ min: min_project_length, max: max_project_length }"
                >
                    Project short name must have between %{ min } and %{ max } characters length. It
                    can only contain alphanumerical characters and dashes. Must start with a letter.
                </translate>
            </p>
        </div>
    </div>
</template>
<script lang="ts">
import EventBus from "../../../helpers/event-bus";
import slugify from "slugify";
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { useStore } from "../../../stores/root";

@Component
export default class ProjectShortName extends Vue {
    root_store = useStore();

    override $refs!: {
        shortname: HTMLFormElement;
    };

    project_name = "";
    slugified_project_name = "";
    has_slug_error = false;
    is_in_edit_mode = false;

    min_project_length = 3;
    max_project_length = 30;

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
        if (this.root_store.has_error || this.is_in_edit_mode) {
            return;
        }

        this.has_slug_error = false;
        this.project_name = value;

        slugify.extend({
            "+": "-",
            ".": "-",
            "~": "-",
            "(": "-",
            ")": "-",
            "!": "-",
            ":": "-",
            "@": "-",
            '"': "-",
            "'": "-",
            "*": "-",
            "©": "-",
            "®": "-",
        });
        this.slugified_project_name = slugify(value, { lower: true })
            .replace(/-+/, "-")
            .slice(0, this.max_project_length);
        this.checkValidity(this.slugified_project_name);

        this.$refs.shortname.value = this.slugified_project_name;

        EventBus.$emit("update-project-name", {
            slugified_name: this.slugified_project_name,
            name: this.project_name,
        });
    }

    updateProjectShortName(value: string): void {
        this.checkValidity(value);
        this.slugified_project_name = value;

        EventBus.$emit("update-project-name", {
            slugified_name: value,
            name: this.project_name,
        });
    }

    checkValidity(value: string): void {
        if (this.root_store.has_error) {
            this.is_in_edit_mode = true;
            this.has_slug_error = true;
            this.root_store.resetError();
        }

        if (value.length < this.min_project_length || value.length > this.max_project_length) {
            this.has_slug_error = true;
            return;
        }

        const regexp = RegExp(/^[a-zA-Z][a-zA-Z0-9-]+$/);
        this.has_slug_error = !regexp.test(value);
    }

    shouldDisplaySlug(): boolean {
        if (this.slugified_project_name.length === 0 || this.is_in_edit_mode) {
            return false;
        }

        return !this.root_store.has_error;
    }

    shouldDisplayEditShortName(): boolean {
        if (this.is_in_edit_mode) {
            return true;
        }

        return this.root_store.has_error;
    }
}
</script>
