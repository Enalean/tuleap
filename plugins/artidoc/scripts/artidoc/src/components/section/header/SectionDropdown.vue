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
    <div class="tlp-dropdown" v-if="!is_pending">
        <button
            type="button"
            v-bind:title="trigger_title"
            class="tlp-button-secondary tlp-button-outline artidoc-dropdown-trigger"
            data-test="artidoc-dropdown-trigger"
            ref="trigger"
        >
            <i class="fa-solid fa-ellipsis-vertical fa-fw" role="img"></i>
        </button>
        <div ref="menu" class="tlp-dropdown-menu tlp-dropdown-menu-on-icon" role="menu">
            <a v-bind:href="artifact_url" class="tlp-dropdown-menu-item" role="menuitem">
                <i
                    class="tlp-dropdown-menu-item-icon fa-solid fa-fw fa-arrow-right"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Go to artifact") }}
            </a>
            <template v-if="is_section_editable">
                <span class="tlp-dropdown-menu-separator" role="separator"></span>
                <button
                    type="button"
                    v-on:click="onDelete"
                    class="tlp-dropdown-menu-item"
                    role="menuitem"
                    data-test="delete"
                    v-bind:title="remove_title"
                >
                    <i
                        class="fa-solid tlp-dropdown-menu-item-icon fa-trash fa-fw"
                        aria-hidden="true"
                    ></i>
                    <span>{{ $gettext("Remove") }}</span>
                </button>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { SectionEditor } from "@/composables/useSectionEditor";
import { useGettext } from "vue3-gettext";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { isPendingArtifactSection, isArtifactSection } from "@/helpers/artidoc-section.type";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { computed, ref, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { moveDropdownMenuInDocumentBody } from "@/helpers/move-dropdownmenu-in-document-body";

const configuration = strictInject(CONFIGURATION_STORE);

const { $gettext } = useGettext();
const props = defineProps<{
    editor: SectionEditor;
    section: ArtidocSection;
}>();
const { deleteSection } = props.editor.editor_actions;
const is_section_editable = props.editor.editor_state.is_section_editable;
const is_pending = computed(() => isPendingArtifactSection(props.section));
const artifact_url = computed(() =>
    isArtifactSection(props.section) ? `/plugins/tracker/?aid=${props.section.artifact.id}` : "",
);
const trigger = ref<HTMLElement | null>(null);
const menu = ref<HTMLElement | null>(null);

const remove_title = $gettext(
    "Remove the section from this document. Corresponding artifact won't be deleted.",
);
const trigger_title = $gettext("Open contextual menu");

let dropdown: Dropdown | null = null;

watch(trigger, () => {
    if (dropdown === null && trigger.value && menu.value) {
        moveDropdownMenuInDocumentBody(document, menu.value);
        dropdown = createDropdown(trigger.value, {
            dropdown_menu: menu.value,
        });
    }
});

function onDelete(): void {
    deleteSection(configuration.selected_tracker.value);
}
</script>

<style lang="scss" scoped>
@use "pkg:@tuleap/burningparrot-theme/css/includes/global-variables";

$button-size: 24px;

.tlp-dropdown {
    position: sticky;
    top: calc(var(--tlp-small-spacing) + #{global-variables.$navbar-height});
    align-self: flex-start;
    margin: var(--tlp-small-spacing) 0 0;
}

.artidoc-dropdown-trigger {
    width: $button-size;
    height: $button-size;
    padding: 0;
    border: var(--tuleap-artidoc-section-background);
    border-radius: 50%;
    background: var(--tuleap-artidoc-section-background);
    box-shadow: none;

    &:focus {
        box-shadow: var(--tlp-shadow-focus);
    }
}
</style>
