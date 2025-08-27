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
    <div class="tlp-dropdown" v-if="should_display_dropdown">
        <button
            type="button"
            v-bind:title="trigger_title"
            class="tlp-button-primary tlp-button-ellipsis"
            ref="trigger"
            data-test="document-actions-button"
        >
            <i class="fa-solid fa-ellipsis" role="img"></i>
        </button>
        <div class="tlp-dropdown-menu" role="menu">
            <configuration-modal-trigger v-if="can_user_edit_document" />
            <switch-to-fullscreen />
            <pdf-export-menu-item v-if="should_display_pdf_menu_item" />
        </div>
        <configuration-modal v-if="can_user_edit_document" />
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { computed, ref, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import ConfigurationModalTrigger from "@/components/configuration/ConfigurationModalTrigger.vue";
import { IS_LOADING_SECTIONS } from "@/is-loading-sections-injection-key";
import SwitchToFullscreen from "@/components/SwitchToFullscreen.vue";

const { $gettext } = useGettext();

const trigger = ref<HTMLElement | null>(null);

const trigger_title = $gettext("Open contextual menu");

let dropdown: Dropdown | null = null;

const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const is_loading_sections = strictInject(IS_LOADING_SECTIONS);
const { saved_sections } = strictInject(SECTIONS_COLLECTION);
const should_display_pdf_menu_item = computed(
    () => !is_loading_sections.value && saved_sections.value.length > 0,
);

const should_display_dropdown = should_display_pdf_menu_item.value || can_user_edit_document;

watch(trigger, () => {
    if (dropdown === null && trigger.value) {
        dropdown = createDropdown(trigger.value);
    }
});
</script>

<style lang="scss" scoped>
@media print {
    .tlp-dropdown {
        display: none;
    }
}
</style>
