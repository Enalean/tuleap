<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <form
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit.prevent="createNewFile"
        ref="file_creation_modal_root_anchor"
    >
        <modal-header
            v-bind:modal-title="$gettext('Create a new file')"
            v-bind:aria-labelled-by="aria_labelled_by"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <document-global-property-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create document')"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:data-test="`document-modal-submit-button-create-file`"
            v-bind:icon-submit-button-class="``"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import DocumentGlobalPropertyForCreate from "./PropertiesForCreate/DocumentGlobalPropertyForCreate.vue";
import type { DefaultFileItem, Folder, RootState } from "../../../../type";
import { TYPE_FILE } from "../../../../constants";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import emitter from "../../../../helpers/emitter";
import {
    useActions,
    useNamespacedMutations,
    useNamespacedState,
    useState,
} from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import { onBeforeUnmount, onMounted, ref } from "vue";
import { transformStatusPropertyForItemCreation } from "../../../../helpers/properties-helpers/creation-data-transformatter-helper";
import type { ConfigurationState } from "../../../../store/configuration";
import { buildFakeItem } from "../../../../helpers/item-builder";

const props = defineProps<{ parent: Folder; droppedFile: File }>();

const emit = defineEmits<{
    (e: "close-file-creation-modal"): void;
}>();

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);
const { is_status_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_status_property_used">
>("configuration", ["is_status_property_used"]);

const { createNewItem } = useActions(["createNewItem"]);
const { resetModalError } = useNamespacedMutations("error", ["resetModalError"]);

const modal = ref<Modal | null>(null);
const is_loading = ref(false);
const item = ref<DefaultFileItem>(getDefaultItem());

const aria_labelled_by = ref("document-file-creation-modal");

const file_creation_modal_root_anchor = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (file_creation_modal_root_anchor.value) {
        modal.value = createModal(file_creation_modal_root_anchor.value, { destroy_on_hide: true });
        modal.value.addEventListener("tlp-modal-hidden", close);
        modal.value.show();
    }
    emitter.on("update-status-property", updateStatusValue);
    emitter.on("update-title-property", updateTitleValue);
    emitter.on("update-description-property", updateDescriptionValue);
    transformStatusPropertyForItemCreation(item.value, props.parent, is_status_property_used.value);
});

onBeforeUnmount((): void => {
    if (modal.value !== null) {
        modal.value.removeEventListener("tlp-modal-hidden", close);
    }
    emitter.off("update-status-property", updateStatusValue);
    emitter.off("update-title-property", updateTitleValue);
    emitter.off("update-description-property", updateDescriptionValue);
});

function getDefaultItem(): DefaultFileItem {
    return {
        title: "",
        description: "",
        type: TYPE_FILE,
        file_properties: {
            file: props.droppedFile,
        },
        status: "none",
    };
}

function close(): void {
    if (modal.value !== null) {
        modal.value.removeBackdrop();
        emit("close-file-creation-modal");
        item.value = getDefaultItem();
    }
}

async function createNewFile(): Promise<void> {
    is_loading.value = true;
    resetModalError({});
    await createNewItem([item.value, props.parent, current_folder.value, buildFakeItem()]);
    is_loading.value = false;

    if (!has_modal_error.value) {
        item.value = getDefaultItem();
        close();
    }
}

function updateStatusValue(status: string) {
    item.value.status = status;
}

function updateTitleValue(title: string): void {
    item.value.title = title;
}

function updateDescriptionValue(description: string): void {
    item.value.description = description;
}
</script>
