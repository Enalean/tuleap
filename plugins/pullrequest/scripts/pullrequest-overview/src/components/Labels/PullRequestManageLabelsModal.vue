<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div
        id="pull-request-manage-labels-modal"
        class="tlp-modal"
        role="dialog"
        aria-labelledby="manage-labels-modal-title"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 id="manage-labels-modal-title" class="tlp-modal-title">
                {{ $gettext("Manage pull-request's labels") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <div class="tlp-form-element">
                <label class="tlp-label" for="manage-labels-modal-select">{{
                    $gettext("Labels")
                }}</label>
                <select id="manage-labels-modal-select" ref="labels_input"></select>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                data-test="save-labels-button"
                v-bind:disabled="is_saving"
                v-on:click="saveLabels"
            >
                <i
                    class="tlp-button-icon fa-solid"
                    v-bind:class="{
                        'fa-circle-notch fa-spin': is_saving,
                        'fa-pencil': !is_saving,
                    }"
                ></i>
                {{ $gettext("Save changes") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { createLazybox } from "@tuleap/lazybox";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";

import { LabelsAutocompleter } from "./autocomplete/LabelsAutocompleter";
import { GroupOfLabelsBuilder } from "./autocomplete/GroupOfLabelsBuilder";
import {
    getAssignableLabelsTemplate,
    getAssignedLabelTemplate,
    getSelectedLabels,
} from "./autocomplete/AssignableLabelTemplate";
import { findLabelsWithIds } from "./autocomplete/LabelFinder";

const { $gettext } = useGettext();

const props = defineProps<{
    current_labels: ReadonlyArray<ProjectLabel>;
    project_labels: ReadonlyArray<ProjectLabel>;
    post_edition_callback: (labels: ProjectLabel[]) => void;
    on_cancel_callback: () => void;
}>();

const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);
const is_saving = ref(false);
const labels_input = ref<HTMLSelectElement | undefined>();
const currently_selected_labels = ref<ProjectLabel[]>([]);

const cancel = () => {
    props.on_cancel_callback();
};

function saveLabels() {
    if (modal_instance.value) {
        modal_instance.value.hide();
    }

    props.post_edition_callback(currently_selected_labels.value);
}

onMounted((): void => {
    if (!modal_element.value || !labels_input.value) {
        return;
    }

    modal_instance.value = createModal(modal_element.value, {
        destroy_on_hide: false,
        keyboard: false,
    });

    modal_instance.value.show();
    modal_instance.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);

    initlabelsAutocompleter(labels_input.value);
});

onBeforeUnmount(() => {
    if (modal_instance.value) {
        modal_instance.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);
    }
});

function initlabelsAutocompleter(select: HTMLSelectElement): void {
    const group_builder = GroupOfLabelsBuilder($gettext);
    const autocompleter = LabelsAutocompleter(group_builder);

    const current_labels_ids = props.current_labels.map(({ id }) => id);
    const project_labels = props.project_labels.map((label) => ({
        value: {
            ...label,
        },
        is_disabled: false,
    }));

    const lazybox = createLazybox(select, {
        is_multiple: true,
        placeholder: $gettext("Select labels"),
        templating_callback: getAssignableLabelsTemplate,
        selection_badge_callback: getAssignedLabelTemplate,
        search_input_callback: (query) => {
            autocompleter.autocomplete(
                lazybox,
                project_labels,
                currently_selected_labels.value,
                query
            );
        },
        selection_callback: (selected_labels) => {
            currently_selected_labels.value = getSelectedLabels(selected_labels);
        },
    });

    lazybox.setDropdownContent([group_builder.buildWithLabels(project_labels)]);
    lazybox.replaceSelection(findLabelsWithIds(project_labels, current_labels_ids));
}
</script>
