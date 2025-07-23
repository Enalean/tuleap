<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
  -
  -->

<template>
    <form
        class="tlp-modal"
        role="dialog"
        aria-labelledby="document-new-item-modal"
        v-on:submit="addDocument"
        enctype="multipart/form-data"
        data-test="document-new-item-modal"
        ref="form"
    >
        <modal-header
            v-bind:modal-title="$gettext('New document')"
            aria-labelled-by="document-new-item-modal"
        >
            <span
                class="tlp-badge-primary tlp-badge-outline"
                v-bind:class="alternative_badge_class"
                v-if="from_alternative_extension.length > 0"
            >
                .{{ from_alternative_extension }}
            </span>
        </modal-header>
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <document-global-property-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
            >
                <link-properties
                    v-if="item.type === TYPE_LINK"
                    name="properties"
                    v-bind:value="item.link_properties.link_url"
                />
                <wiki-properties
                    v-if="item.type === TYPE_WIKI"
                    v-bind:value="item.wiki_properties.page_name"
                    name="properties"
                />
                <embedded-properties
                    v-if="item.type === TYPE_EMBEDDED"
                    v-bind:value="item.embedded_properties.content"
                    name="properties"
                />
                <file-properties
                    v-if="item.type === TYPE_FILE && !is_from_alternative"
                    v-bind:value="item.file_properties"
                    name="properties"
                />
            </document-global-property-for-create>
            <other-information-properties-for-create
                v-bind:currently-updated-item="item"
                v-model="item.obsolescence_date"
                v-bind:value="item.obsolescence_date"
            />
            <creation-modal-permissions-section
                v-if="item.permissions_for_groups"
                v-bind:value="item.permissions_for_groups"
                v-bind:project_ugroups="project_ugroups"
            />
        </div>

        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            aria-labelled-by="document-new-item-modal"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-item"
        />
    </form>
</template>

<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import {
    CAN_MANAGE,
    CAN_READ,
    CAN_WRITE,
    TYPE_EMBEDDED,
    TYPE_FILE,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../../constants";
import DocumentGlobalPropertyForCreate from "./PropertiesForCreate/DocumentGlobalPropertyForCreate.vue";
import LinkProperties from "../PropertiesForCreateOrUpdate/LinkProperties.vue";
import WikiProperties from "../PropertiesForCreateOrUpdate/WikiProperties.vue";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import EmbeddedProperties from "../PropertiesForCreateOrUpdate/EmbeddedProperties.vue";
import FileProperties from "../PropertiesForCreateOrUpdate/FileProperties.vue";
import OtherInformationPropertiesForCreate from "./PropertiesForCreate/OtherInformationPropertiesForCreate.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import { handleErrors } from "../../../../store/actions-helpers/handle-errors";
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";
import {
    transformCustomPropertiesForItemCreation,
    transformStatusPropertyForItemCreation,
} from "../../../../helpers/properties-helpers/creation-data-transformatter-helper";
import type {
    CreateItemEvent,
    UpdateCustomEvent,
    UpdateMultipleListValueEvent,
    UpdatePermissionsEvent,
} from "../../../../helpers/emitter";
import emitter from "../../../../helpers/emitter";
import { isFile, isFolder } from "../../../../helpers/type-check-helper";
import { getEmptyOfficeFileFromMimeType } from "../../../../helpers/office/get-empty-office-file";
import { buildFakeItem } from "../../../../helpers/item-builder";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState, useState, useStore } from "vuex-composition-helpers";
import type { FakeItem, Item, RootState } from "../../../../type";
import type { ConfigurationState } from "../../../../store/configuration";
import type { ErrorState } from "../../../../store/error/module";
import type { PermissionsState } from "../../../../store/permissions/permissions-default-state";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const $store = useStore();

const item = ref({});
const is_displayed = ref<boolean>(false);
const is_loading = ref<boolean>(false);
const parent = ref<Item>({});
const is_from_alternative = ref<boolean>(false);
const from_alternative_extension = ref<string>("");
const alternative_badge_class = ref<string>("");
const fake_item = ref<FakeItem>(buildFakeItem());
const form = ref<HTMLFormElement>();
let modal: Modal | null = null;

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const { project_id, is_status_property_used, user_locale } = useNamespacedState<
    Pick<ConfigurationState, "project_id" | "is_status_property_used" | "user_locale">
>("configuration", ["project_id", "is_status_property_used", "user_locale"]);
const { has_modal_error } = useNamespacedState<Pick<ErrorState, "has_modal_error">>("error", [
    "has_modal_error",
]);
const { project_ugroups } = useNamespacedState<Pick<PermissionsState, "project_ugroups">>(
    "permissions",
    ["project_ugroups"],
);

const submit_button_label = computed(() => {
    if (is_from_alternative.value) {
        return $gettext("Create and edit document");
    }

    return $gettext("Create document");
});

onMounted(() => {
    modal = createModal(form.value);
    emitter.on("createItem", show);
    emitter.on("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    modal.addEventListener("tlp-modal-hidden", reset);
    emitter.on("update-status-property", updateStatusValue);
    emitter.on("update-title-property", updateTitleValue);
    emitter.on("update-description-property", updateDescriptionValue);
    emitter.on("update-custom-property", updateCustomProperty);
    emitter.on("update-obsolescence-date-property", updateObsolescenceDate);
    emitter.on("update-link-properties", updateLinkProperties);
    emitter.on("update-wiki-properties", updateWikiProperties);
    emitter.on("update-embedded-properties", updateEmbeddedContent);
    emitter.on("update-file-properties", updateFilesProperties);
    emitter.on("update-permissions", updateUGroup);
});

onBeforeUnmount(() => {
    emitter.off("createItem", show);
    emitter.off("update-multiple-properties-list-value", updateMultiplePropertiesListValue);
    modal?.removeEventListener("tlp-modal-hidden", reset);
    emitter.off("update-status-property", updateStatusValue);
    emitter.off("update-title-property", updateTitleValue);
    emitter.off("update-description-property", updateDescriptionValue);
    emitter.off("update-custom-property", updateCustomProperty);
    emitter.off("update-obsolescence-date-property", updateObsolescenceDate);
    emitter.off("update-link-properties", updateLinkProperties);
    emitter.off("update-wiki-properties", updateWikiProperties);
    emitter.off("update-file-properties", updateFilesProperties);
    emitter.off("update-permissions", updateUGroup);
    emitter.off("update-embedded-properties", updateEmbeddedContent);
});

function getDefaultItem() {
    return {
        title: "",
        description: "",
        type: TYPE_FILE,
        link_properties: {
            link_url: "",
        },
        wiki_properties: {
            page_name: "",
        },
        file_properties: {
            file: {},
        },
        embedded_properties: {
            content: "",
        },
        obsolescence_date: "",
        properties: null,
        permissions_for_groups: {
            can_read: [],
            can_write: [],
            can_manage: [],
        },
        status: "none",
    };
}

async function show(event: CreateItemEvent): Promise<void> {
    item.value = getDefaultItem();
    item.value.type = event.type;

    is_from_alternative.value = false;
    from_alternative_extension.value = "";
    alternative_badge_class.value = "";
    if ("from_alternative" in event && event.from_alternative) {
        const office_file = await getEmptyOfficeFileFromMimeType(
            user_locale.value,
            event.from_alternative.mime_type,
        );
        item.value.file_properties.file = office_file.file;
        from_alternative_extension.value = office_file.extension;
        alternative_badge_class.value = office_file.badge_class;
        is_from_alternative.value = true;
    }

    parent.value = event.item;
    addParentPropertiesToDefaultItem();
    if ("permissions_for_groups" in parent.value) {
        item.value.permissions_for_groups = JSON.parse(
            JSON.stringify(parent.value.permissions_for_groups),
        );
    }

    if (parent.value.obsolescence_date) {
        item.value.obsolescence_date = parent.value.obsolescence_date;
    }

    transformCustomPropertiesForItemCreation(item.value.properties);
    if (isFolder(parent.value)) {
        transformStatusPropertyForItemCreation(
            item.value,
            parent.value,
            is_status_property_used.value,
        );
    }

    is_displayed.value = true;
    modal?.show();
    try {
        await $store.dispatch("permissions/loadProjectUserGroupsIfNeeded", project_id.value);
    } catch (err) {
        await handleErrors($store, err);
        modal?.hide();
    }
}

function reset(): void {
    $store.commit("error/resetModalError");
    is_displayed.value = false;
    is_loading.value = false;
    item.value = getDefaultItem();
    is_from_alternative.value = false;
    from_alternative_extension.value = "";
    alternative_badge_class.value = "";
}

async function addDocument(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    is_loading.value = true;
    $store.commit("error/resetModalError");
    const created_item = await $store.dispatch("createNewItem", [
        item.value,
        parent.value,
        current_folder.value,
        fake_item.value,
    ]);

    if (is_from_alternative.value) {
        const redirectToEditor = async ({ id }) => {
            if (created_item.id === id) {
                emitter.off("new-item-has-just-been-created", redirectToEditor);
                const item = await $store.dispatch("loadDocument", id);
                if (isFile(item) && item.file_properties && item.file_properties.open_href) {
                    window.location.href = item.file_properties.open_href;
                }
            }
        };
        emitter.on("new-item-has-just-been-created", redirectToEditor);
    }

    is_loading.value = false;
    if (!has_modal_error.value) {
        modal?.hide();
    }
}

function addParentPropertiesToDefaultItem(): void {
    const parent_properties = getCustomProperties(parent.value);

    const formatted_properties = transformCustomPropertiesForItemCreation(parent_properties);

    if (formatted_properties.length > 0) {
        item.value.properties = formatted_properties;
    }
}

function updateMultiplePropertiesListValue(event: UpdateMultipleListValueEvent): void {
    if (!item.value.properties) {
        return;
    }
    const item_properties = item.value.properties.find(
        (property) => property.short_name === event.detail.id,
    );
    item_properties.list_value = event.detail.value;
}

function updateStatusValue(status: string): void {
    item.value.status = status;
}

function updateTitleValue(title: string): void {
    item.value.title = title;
    if (is_from_alternative.value) {
        item.value.file_properties.file = new File(
            [item.value.file_properties.file],
            item.value.title + "." + from_alternative_extension.value,
            { type: item.value.file_properties.file.type },
        );
    }
}

function updateDescriptionValue(description: string): void {
    item.value.description = description;
}

function updateCustomProperty(event: UpdateCustomEvent): void {
    if (!item.value.properties) {
        return;
    }
    const item_properties = item.value.properties.find(
        (property) => property.short_name === event.property_short_name,
    );
    item_properties.value = event.value;
}

function updateObsolescenceDate(obsolescence_date: string): void {
    item.value.obsolescence_date = obsolescence_date;
}

function updateFilesProperties(file_properties: { FileProperties: FileProperties }): void {
    item.value.file_properties = file_properties;
}

function updateLinkProperties(url: string): void {
    if (!item.value.link_properties) {
        return;
    }
    item.value.link_properties.link_url = url;
}

function updateWikiProperties(page_name: string): void {
    if (!item.value.wiki_properties) {
        return;
    }
    item.value.wiki_properties.page_name = page_name;
}

function updateEmbeddedContent(content: string): void {
    if (!item.value.embedded_properties) {
        return;
    }
    item.value.embedded_properties.content = content;
}

function updateUGroup(event: UpdatePermissionsEvent): void {
    if (!item.value.permissions_for_groups) {
        return;
    }
    switch (event.label) {
        case CAN_READ:
            item.value.permissions_for_groups.can_read = event.value;
            break;
        case CAN_WRITE:
            item.value.permissions_for_groups.can_write = event.value;
            break;
        case CAN_MANAGE:
            item.value.permissions_for_groups.can_manage = event.value;
            break;
        default:
    }
}
</script>
