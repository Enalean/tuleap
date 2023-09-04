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
                <tuleap-lazybox id="manage-labels-modal-select" ref="labels_input" />
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-bind:disabled="is_saving"
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
                {{ $gettext("Save changes") }}
                <i
                    v-if="is_saving"
                    class="tlp-button-icon-right fa-solid fa-circle-notch fa-spin"
                ></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import "@tuleap/lazybox";
import type { Lazybox } from "@tuleap/lazybox";
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
import { strictInject } from "@tuleap/vue-strict-inject";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import { patchPullRequestLabels } from "../../api/tuleap-rest-querier";
import {
    LABEL_TO_CREATE_TMP_ID,
    LabelsCreationManager,
} from "./autocomplete/LabelsCreationManager";

const { $gettext, interpolate } = useGettext();
const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const props = defineProps<{
    current_labels: ReadonlyArray<ProjectLabel>;
    project_labels: ReadonlyArray<ProjectLabel>;
    post_edition_callback: () => void;
    on_cancel_callback: () => void;
}>();

const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);
const is_saving = ref(false);
const labels_input = ref<Lazybox | undefined>();
const newly_selected_labels = ref<ProjectLabel[]>([]);
const current_labels_ids = props.current_labels.map(({ id }) => id);
const labels_creation_manager = LabelsCreationManager(props.project_labels);

const cancel = () => {
    props.on_cancel_callback();
};

function saveLabels() {
    const newly_selected_labels_ids = newly_selected_labels.value.map(({ id }) => id);
    const added_labels_ids = newly_selected_labels_ids.filter(
        (id) => !current_labels_ids.includes(id),
    );
    const removed_labels_ids = current_labels_ids.filter(
        (id) => !newly_selected_labels_ids.includes(id),
    );

    is_saving.value = true;

    patchPullRequestLabels(
        pull_request_id,
        added_labels_ids,
        removed_labels_ids,
        labels_creation_manager.getLabelsToCreate(),
    )
        .match(() => {
            modal_instance.value?.hide();
            props.post_edition_callback();
        }, displayTuleapAPIFault)
        .finally(() => {
            is_saving.value = false;
        });
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

function initlabelsAutocompleter(lazybox: Lazybox): void {
    const group_builder = GroupOfLabelsBuilder($gettext);
    const autocompleter = LabelsAutocompleter(group_builder);

    const project_labels = props.project_labels.map((label) => ({
        value: {
            ...label,
        },
        is_disabled: false,
    }));

    lazybox.options = {
        is_multiple: true,
        placeholder: $gettext("Select labels"),
        templating_callback: getAssignableLabelsTemplate,
        selection_badge_callback: getAssignedLabelTemplate,
        search_input_callback: (query) => {
            autocompleter.autocomplete(lazybox, project_labels, newly_selected_labels.value, query);
        },
        selection_callback: (selected_labels) => {
            const labels_in_selection = getSelectedLabels(selected_labels);
            labels_creation_manager.registerLabelsToCreate(labels_in_selection);

            newly_selected_labels.value = labels_in_selection.filter(
                ({ id }) => id !== LABEL_TO_CREATE_TMP_ID,
            );
        },
        new_item_label_callback: (item_name) =>
            item_name !== ""
                ? interpolate($gettext(`Create a new label "%{label}"`), { label: item_name })
                : $gettext("Create a new label"),
        new_item_clicked_callback: (item_name: string) => {
            if (!labels_creation_manager.canLabelBeCreated(item_name)) {
                return;
            }

            project_labels.push({
                value: labels_creation_manager.getTemporaryLabel(item_name),
                is_disabled: false,
            });

            lazybox.replaceSelection(
                findLabelsWithIds(project_labels, [...current_labels_ids, LABEL_TO_CREATE_TMP_ID]),
            );
        },
    };

    lazybox.replaceDropdownContent([group_builder.buildWithLabels(project_labels)]);
    lazybox.replaceSelection(findLabelsWithIds(project_labels, current_labels_ids));
}
</script>
