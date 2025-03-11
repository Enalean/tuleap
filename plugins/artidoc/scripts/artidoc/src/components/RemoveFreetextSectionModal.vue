<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
        class="tlp-modal tlp-modal-danger"
        aria-labelledby="artidoc-remove-freetext-section-modal-title"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="artidoc-remove-freetext-section-modal-title">
                {{ $gettext("Remove a freetext section") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:title="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" role="img"></i>
            </button>
        </div>

        <div class="tlp-modal-feedback">
            <div class="tlp-alert-warning">
                {{ $gettext("The removal of a freetext section is definitive") }}
            </div>
        </div>

        <div class="tlp-modal-body">
            <p>{{ $gettext("Are you sure you want to delete this freetext section?") }}</p>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                v-on:click="closeModal"
                data-test="cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>

            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="remove-button"
                v-on:click="onDelete"
            >
                {{ $gettext("Remove section") }}
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { ref } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { createModal } from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { RemoveSections } from "@/sections/remove/SectionsRemover";
import { REMOVE_FREETEXT_SECTION_MODAL } from "@/composables/useRemoveFreetextSectionModal";
import { SET_GLOBAL_ERROR_MESSAGE } from "@/global-error-message-injection-key";

const gettext_provider = useGettext();
const { $gettext } = gettext_provider;

const props = defineProps<{
    remove_sections: RemoveSections;
}>();

const setGlobalErrorMessage = strictInject(SET_GLOBAL_ERROR_MESSAGE);

strictInject(REMOVE_FREETEXT_SECTION_MODAL).registerHandler(openModal);

const modal_element = ref<HTMLElement | undefined>(undefined);

let section_to_remove: ReactiveStoredArtidocSection | null = null;
let modal: Modal | null = null;

function closeModal(): void {
    if (!modal) {
        return;
    }
    modal.hide();
}

function openModal(section: ReactiveStoredArtidocSection): void {
    section_to_remove = section;
    if (modal_element.value) {
        modal = createModal(modal_element.value);
    }

    if (modal) {
        modal.show();
    }
}

function onDelete(): void {
    if (section_to_remove) {
        props.remove_sections.removeSection(section_to_remove).match(
            () => {
                closeModal();
            },
            (fault) => {
                closeModal();
                setGlobalErrorMessage({
                    message: $gettext("An error occurred while removing the freetext section."),
                    details: fault.toString(),
                });
            },
        );
    }
}
</script>
