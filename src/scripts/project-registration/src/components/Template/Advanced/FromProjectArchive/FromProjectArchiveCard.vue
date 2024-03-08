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
    <div class="project-registration-template-card">
        <input
            type="radio"
            id="project-registration-tuleap-template-archive-project"
            class="project-registration-selected-template"
            name="selected-template"
            v-on:change="setSelectedOption(option_name)"
        />

        <label
            class="tlp-card tlp-card-selectable project-registration-template-label"
            for="project-registration-tuleap-template-archive-project"
        >
            <div class="project-registration-template-glyph">
                <from-project-svg />
            </div>
            <div class="project-registration-template-content">
                <h4 class="project-registration-template-card-title">
                    {{ $gettext("From project template upload") }}
                </h4>
                <div
                    class="project-registration-template-card-description"
                    data-test="archive-project-description"
                    v-if="!root_store.is_advanced_option_selected(option_name)"
                >
                    {{
                        $gettext(
                            "Create a project based on a template exported from another platform",
                        )
                    }}
                </div>
                <input
                    type="file"
                    accept="application/zip"
                    v-else-if="root_store.is_advanced_option_selected(option_name)"
                    data-test="archive-project-file-input"
                    v-on:input="setSelectedTemplate"
                />
            </div>
        </label>
    </div>
</template>

<script setup lang="ts">
import FromProjectSvg from "./FromProjectSvg.vue";
import type { AdvancedOptions, ProjectArchiveTemplateData } from "../../../../type";
import { useStore } from "../../../../stores/root";

const root_store = useStore();

const option_name = "from_project_archive";

function setSelectedOption(option_name: AdvancedOptions): void {
    root_store.resetSelectedTemplate();
    root_store.setAdvancedActiveOption(option_name);
}

function setSelectedTemplate(event: Event): void {
    if (!(event.target instanceof HTMLInputElement) || event.target.files === null) {
        return;
    }
    const files = event.target.files;

    if (files[0] === null) {
        return;
    }

    const archive = files[0];

    const template: ProjectArchiveTemplateData = {
        id: "from_project_archive",
        title: "From project template upload",
        is_built_in: false,
        glyph: "",
        description: "Create a project based on a template exported from another platform",
        archive,
    };
    root_store.setSelectedTemplate(template);
}
</script>
