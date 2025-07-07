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
            <span>{{ $gettext("Project shortname:") }}</span>
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
                <span>{{ $gettext("Project shortname") }}</span>
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
                v-on:input="updateProjectShortName(shortname ? shortname.value : '')"
                v-bind:value="slugified_project_name"
                data-test="new-project-shortname"
            />
            <p class="tlp-text-info">
                <i class="far fa-fw fa-life-ring"></i>
                <span>{{
                    $gettext("Must start with a letter, without spaces nor punctuation.")
                }}</span>
            </p>
            <p class="tlp-text-danger" v-if="has_slug_error" data-test="has-error-slug">
                <i class="fa fa-fw fa-exclamation-circle"></i>
                {{ error_project_short_name }}
            </p>
        </div>
    </div>
</template>
<script setup lang="ts">
import slugify from "slugify";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useStore } from "../../../stores/root";
import { useGettext } from "vue3-gettext";
import emitter from "../../../helpers/emitter";

const root_store = useStore();

const shortname = ref<InstanceType<typeof HTMLFormElement>>();
const { $gettext } = useGettext();

const project_name = ref("");
const slugified_project_name = ref("");
const has_slug_error = ref(false);
const is_in_edit_mode = ref(false);

const min_project_length = ref(3);
const max_project_length = ref(30);

const error_project_short_name = $gettext(
    "Project short name must have between %{ min } and %{ max } characters length. It can only contain alphanumerical characters and dashes. Must start with a letter.",
    {
        min: String(min_project_length),
        max: String(max_project_length),
    },
);

const should_user_correct_shortname = computed((): string => {
    if (shouldDisplayEditShortName()) {
        return "project-short-name-edit-section";
    }

    return "project-short-name-hidden-section";
});

onMounted(() => {
    emitter.on("slugify-project-name", slugifyProjectShortName);
});

onBeforeUnmount((): void => {
    emitter.off("slugify-project-name", slugifyProjectShortName);
});

function slugifyProjectShortName(value: string): void {
    if (root_store.has_error || is_in_edit_mode.value) {
        return;
    }

    has_slug_error.value = false;
    project_name.value = value;

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
        _: "-",
    });
    slugified_project_name.value = slugify(value, { lower: true })
        .replace(/-+/, "-")
        .slice(0, max_project_length.value);
    checkValidity(slugified_project_name.value);

    if (shortname.value) {
        shortname.value.value = slugified_project_name.value;
    }

    emitter.emit("update-project-name", {
        slugified_name: slugified_project_name.value,
        name: project_name.value,
    });
}

function updateProjectShortName(value: string): void {
    checkValidity(value);
    slugified_project_name.value = value;

    emitter.emit("update-project-name", {
        slugified_name: value,
        name: project_name.value,
    });
}

function checkValidity(value: string): void {
    if (root_store.has_error) {
        is_in_edit_mode.value = true;
        has_slug_error.value = true;
        root_store.resetError();
    }

    if (value.length < min_project_length.value || value.length > max_project_length.value) {
        has_slug_error.value = true;
        return;
    }

    const regexp = RegExp(/^[a-zA-Z][a-zA-Z0-9-]+$/);
    has_slug_error.value = !regexp.test(value);
}

function shouldDisplaySlug(): boolean {
    if (slugified_project_name.value.length === 0 || is_in_edit_mode.value) {
        return false;
    }

    return !root_store.has_error;
}

function shouldDisplayEditShortName(): boolean {
    if (is_in_edit_mode.value) {
        return true;
    }

    return root_store.has_error;
}
</script>
