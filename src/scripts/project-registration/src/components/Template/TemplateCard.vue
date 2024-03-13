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
    <div class="project-registration-template-card">
        <input
            type="radio"
            v-bind:id="'project-registration-tuleap-template-' + props.template.id"
            v-bind:value="props.template.id"
            v-bind:checked="is_checked"
            class="project-registration-selected-template"
            name="selected-template"
            data-test="project-registration-radio"
            v-on:change="storeSelectedTemplate()"
        />

        <label
            class="tlp-card tlp-card-selectable project-registration-template-label"
            data-test="project-registration-card-label"
            v-bind:for="'project-registration-tuleap-template-' + props.template.id"
        >
            <div
                class="project-registration-template-glyph"
                v-dompurify-html:svg="props.template.glyph"
                data-test="scrum-template-svg"
            />
            <div class="project-registration-template-content">
                <h4 class="project-registration-template-card-title">{{ props.template.title }}</h4>
                <div class="project-registration-template-card-description-content">
                    <span class="project-registration-template-card-description">
                        {{ props.template.description }}
                    </span>
                </div>
            </div>
        </label>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { TemplateData } from "../../type";
import { useStore } from "../../stores/root";

const props = defineProps<{ template: TemplateData }>();

const root_store = useStore();

function storeSelectedTemplate(): void {
    root_store.setSelectedTemplate(props.template);
}

const is_checked = computed((): boolean => {
    return root_store.is_currently_selected_template(props.template);
});
</script>
