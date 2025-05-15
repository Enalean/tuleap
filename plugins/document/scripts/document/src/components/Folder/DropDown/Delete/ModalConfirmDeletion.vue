<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-confirm-deletion-modal-title"
        ref="delete_modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-confirm-deletion-modal-title">
                {{ $gettext("Hold on a second!") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_title"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <modal-feedback />
        <div class="tlp-modal-body">
            <p>{{ modal_description }}</p>
            <div
                class="tlp-alert-warning"
                v-if="is_item_a_folder(item)"
                data-test="delete-folder-warning"
            >
                {{
                    $gettext(
                        "When you delete a folder, all its content is also deleted. Please think wisely!",
                    )
                }}
            </div>
            <delete-associated-wiki-page-checkbox
                v-if="can_wiki_checkbox_be_shown"
                v-model="additional_options"
                v-bind:item="item"
                v-bind:wiki-page-referencers="wiki_page_referencers"
                data-test="delete-wiki-checkbox"
            />
            <span class="document-confirm-deletion-modal-wiki-page-referencers-loading">
                <i
                    class="fa-solid fa-spin fa-circle-notch"
                    v-if="is_item_a_wiki(item) && wiki_page_referencers_loading"
                ></i>
            </span>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="document-confirm-deletion-button"
                v-on:click="doDeleteItem()"
                v-bind:class="{ disabled: is_confirm_button_disabled }"
                v-bind:disabled="is_confirm_button_disabled"
            >
                <i
                    class="tlp-button-icon"
                    v-bind:class="{
                        'fa-solid fa-spin fa-circle-notch': is_an_action_on_going,
                        'fa-solid fa-trash': !is_an_action_on_going,
                    }"
                ></i>
                {{ $gettext("Delete") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import DeleteAssociatedWikiPageCheckbox from "./DeleteAssociatedWikiPageCheckbox.vue";
import { isFolder, isWiki } from "../../../../helpers/type-check-helper";
import type { Item, RootState } from "../../../../type";
import type { ItemPath } from "../../../../store/actions-helpers/build-parent-paths";
import {
    useActions,
    useMutations,
    useNamespacedMutations,
    useNamespacedState,
    useState,
} from "vuex-composition-helpers";
import type { ErrorState } from "../../../../store/error/module";
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useRouter } from "../../../../helpers/use-router";
import { useClipboardStore } from "../../../../stores/clipboard";
import { useStore } from "vuex";
import type { ConfigurationState } from "../../../../store/configuration";

const props = defineProps<{ item: Item }>();

const { $gettext, interpolate } = useGettext();

const { current_folder, currently_previewed_item } = useState<
    Pick<RootState, "current_folder" | "currently_previewed_item">
>(["current_folder", "currently_previewed_item"]);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);

const { deleteItem, getWikisReferencingSameWikiPage } = useActions([
    "deleteItem",
    "getWikisReferencingSameWikiPage",
]);
const { showPostDeletionNotification, updateCurrentlyPreviewedItem } = useMutations([
    "showPostDeletionNotification",
    "updateCurrentlyPreviewedItem",
]);
const { resetModalError } = useNamespacedMutations("error", ["resetModalError"]);

const { project_id, user_id } = useNamespacedState<
    Pick<ConfigurationState, "project_id" | "user_id">
>("configuration", ["project_id", "user_id"]);

const clipboard = useClipboardStore(useStore(), project_id.value, user_id.value);

const modal = ref<Modal | null>(null);

const is_item_being_deleted = ref(false);
const wiki_page_referencers_loading = ref(false);
const additional_options = ref({});
const wiki_page_referencers: Ref<null | Array<ItemPath>> = ref(null);

const is_an_action_on_going = computed((): boolean => {
    return is_item_being_deleted.value || wiki_page_referencers_loading.value;
});

const is_confirm_button_disabled = computed((): boolean => {
    return has_modal_error.value || is_an_action_on_going.value;
});

const can_wiki_checkbox_be_shown = computed((): boolean => {
    return (
        isWiki(props.item) &&
        !wiki_page_referencers_loading.value &&
        wiki_page_referencers.value !== null
    );
});

const router = useRouter();

const close_title = $gettext("Close");
const modal_description = interpolate(
    $gettext('You are about to delete "%{ title }" permanently. Please confirm your action.'),
    { title: props.item.title },
);

const delete_modal = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (delete_modal.value) {
        modal.value = createModal(delete_modal.value, { destroy_on_hide: true });
        modal.value.addEventListener("tlp-modal-hidden", close);
        modal.value.show();
    }

    if (isWiki(props.item) && props.item.wiki_properties.page_id !== null) {
        setWikiPageReferencers();
    }
});

async function doDeleteItem(): Promise<void> {
    const deleted_item_parent_id = props.item.parent_id;
    is_item_being_deleted.value = true;

    await deleteItem({
        item: props.item,
        clipboard,
        additional_wiki_options: additional_options.value,
    });

    if (!has_modal_error.value && modal.value && deleted_item_parent_id) {
        showPostDeletionNotification();
        await redirectToParentFolderIfNeeded(deleted_item_parent_id.toString());

        modal.value.hide();
    }

    is_item_being_deleted.value = false;
}

async function setWikiPageReferencers(): Promise<void> {
    wiki_page_referencers_loading.value = true;

    const referencers = await getWikisReferencingSameWikiPage(props.item);

    wiki_page_referencers_loading.value = false;
    wiki_page_referencers.value = referencers;
}

const emit = defineEmits<{ (e: "delete-modal-closed"): void }>();

function close(): void {
    resetModalError();
    emit("delete-modal-closed");
}

async function redirectToParentFolderIfNeeded(deleted_item_parent_id: string) {
    const is_item_the_current_folder = props.item.id === current_folder.value.id;
    const is_item_being_previewed =
        currently_previewed_item.value !== null &&
        currently_previewed_item.value.id === props.item.id;

    if (!is_item_the_current_folder && !is_item_being_previewed) {
        return;
    }

    updateCurrentlyPreviewedItem(null);
    await router.replace({
        name: "folder",
        params: { item_id: deleted_item_parent_id },
    });
}

function is_item_a_wiki(item: Item): boolean {
    return isWiki(item);
}

function is_item_a_folder(item: Item): boolean {
    return isFolder(item);
}

defineExpose({ can_wiki_checkbox_be_shown });
</script>
