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
    <editor-toolbar
        v-if="can_user_edit_document"
        v-bind:sections="sections_collection"
        v-bind:states_collection="states_collection"
    />
    <notification-container />
    <div class="tlp-card">
        <ul>
            <li
                v-for="section in sections_collection.sections.value"
                v-bind:key="section.value.internal_id"
                v-bind:id="getId(section.value)"
                v-bind:class="{ 'artidoc-section-with-add-button': has_add_button }"
                data-test="artidoc-section"
                v-bind:data-test-type="`${section.value.type}-section`"
            >
                <add-new-section-button
                    class="artidoc-button-add-section-container"
                    v-if="has_add_button"
                    v-bind:position="{ before: section.value.id }"
                    v-bind:sections_inserter="sections_inserter"
                />
                <div
                    class="artidoc-display-level"
                    v-bind:class="{
                        'artidoc-display-level-for-edition': has_add_button,
                        'artidoc-display-level-for-readonly': !has_add_button,
                        [`level-${section.value.level}`]: true,
                    }"
                >
                    {{ section.value.display_level }}
                </div>
                <section-container v-bind:section="section" />
            </li>
        </ul>
        <add-new-section-button
            class="artidoc-button-add-section-container"
            v-if="has_add_button"
            v-bind:position="AT_THE_END"
            v-bind:sections_inserter="sections_inserter"
        />
        <add-existing-section-modal />
        <remove-freetext-section-modal v-bind:remove_sections="sections_remover" />
    </div>
</template>

<script setup lang="ts">
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { AT_THE_END, getSectionsInserter } from "@/sections/insert/SectionsInserter";
import AddNewSectionButton from "@/components/AddNewSectionButton.vue";
import SectionContainer from "@/components/section/SectionContainer.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import AddExistingSectionModal from "@/components/AddExistingSectionModal.vue";
import NotificationContainer from "@/components/NotificationContainer.vue";
import EditorToolbar from "@/components/toolbar/EditorToolbar.vue";
import RemoveFreetextSectionModal from "@/components/RemoveFreetextSectionModal.vue";
import { getSectionsRemover } from "@/sections/remove/SectionsRemover";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { getSectionsNumberer } from "@/sections/levels/SectionsNumberer";

const sections_collection = strictInject(SECTIONS_COLLECTION);
const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);

const sections_numberer = getSectionsNumberer(sections_collection);
const sections_inserter = getSectionsInserter(
    sections_collection,
    states_collection,
    sections_numberer,
);
const sections_remover = getSectionsRemover(sections_collection, states_collection);
const has_add_button = can_user_edit_document;

function getId(section: ArtidocSection): string {
    return `section-${section.id}`;
}
</script>

<style lang="scss" scoped>
@use "@/themes/includes/whitespace";
@use "@/themes/includes/size";

$section-number-padding-left: var(--tlp-small-spacing);
$section-number-padding-right: var(--tlp-medium-spacing);

ul {
    margin: 0;
    padding: 0;
}

li {
    position: relative;

    &::marker {
        color: transparent; // hack to hide the bullet list to be displayed in the margin
    }

    &:first-child {
        > .artidoc-section-container {
            padding-top: var(--tlp-large-spacing);
        }

        > .artidoc-display-level-for-edition {
            &.level-1 {
                top: calc(
                    #{size.$header-level-1-top-offset-for-edition} + var(--tlp-small-spacing)
                );
            }

            &.level-2 {
                top: calc(
                    #{size.$header-level-2-top-offset-for-edition} + var(--tlp-small-spacing)
                );
            }

            &.level-3 {
                top: calc(
                    #{size.$header-level-3-top-offset-for-edition} + var(--tlp-small-spacing)
                );
            }
        }

        > .artidoc-display-level-for-readonly {
            &.level-1 {
                top: calc(
                    #{size.$header-level-1-top-offset-for-readonly} + var(--tlp-small-spacing)
                );
            }

            &.level-2 {
                top: calc(
                    #{size.$header-level-2-top-offset-for-readonly} + var(--tlp-small-spacing)
                );
            }

            &.level-3 {
                top: calc(
                    #{size.$header-level-3-top-offset-for-readonly} + var(--tlp-small-spacing)
                );
            }
        }
    }
}

.artidoc-display-level {
    left: var(--tlp-small-spacing);
    width: calc(
        #{whitespace.$section-left-padding} - #{$section-number-padding-left} - #{$section-number-padding-right}
    );
    color: var(--tlp-dimmed-color-lighter-50);
    font-style: italic;
    font-weight: 600;
    text-align: right;
}

.artidoc-display-level-for-readonly {
    position: relative;

    &.level-1 {
        top: size.$header-level-1-top-offset-for-readonly;
    }

    &.level-2 {
        top: size.$header-level-2-top-offset-for-readonly;
    }

    &.level-3 {
        top: size.$header-level-3-top-offset-for-readonly;
    }
}

.artidoc-display-level-for-edition {
    position: absolute;

    &.level-1 {
        top: size.$header-level-1-top-offset-for-edition;
    }

    &.level-2 {
        top: size.$header-level-2-top-offset-for-edition;
    }

    &.level-3 {
        top: size.$header-level-3-top-offset-for-edition;
    }
}

.tlp-card {
    width: size.$document-width;
    margin: var(--tlp-medium-spacing) 0 var(--tlp-x-large-spacing);
    padding: 0;
    border: 0;
    background-color: var(--tlp-white-color);
    box-shadow: var(--tlp-flyover-shadow);
}
</style>

<style lang="scss">
@use "@/themes/includes/viewport-breakpoint";

.is-aside-expanded + .document-content {
    > .tlp-card {
        @media screen and (max-width: #{viewport-breakpoint.$medium-screen-size-when-document-sidebar-is-expanded}) {
            width: 100%;
            margin: 0;
            box-shadow: none;
        }
    }
}

.is-aside-collapsed + .document-content {
    > .tlp-card {
        @media screen and (max-width: #{viewport-breakpoint.$medium-screen-size-when-document-sidebar-is-collapsed}) {
            width: 100%;
            margin: 0;
            box-shadow: none;
        }
    }
}
</style>
