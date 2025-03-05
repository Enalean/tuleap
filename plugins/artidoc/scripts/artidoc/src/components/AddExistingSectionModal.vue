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
        class="tlp-modal"
        aria-labelledby="artidoc-add-existing-section-modal-title"
        ref="modal_element"
        v-on:submit="onSubmit"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="artidoc-add-existing-section-modal-title">
                {{ title }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:title="close_title"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" role="img"></i>
            </button>
        </div>

        <div class="tlp-modal-feedback" v-if="has_error_message">
            <div class="tlp-alert-danger">
                {{ error_message }}
            </div>
        </div>

        <div class="tlp-modal-body" ref="body" v-if="is_search_allowed">
            <p>{{ explanations }}</p>
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                v-on:click="closeModal"
            >
                {{ $gettext("Cancel") }}
            </button>

            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_submit_button_disabled"
                data-test="submit"
            >
                <i class="tlp-button-icon" v-bind:class="submit_button_icon" aria-hidden="true"></i>
                {{ $gettext("Add section") }}
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed, ref, toRaw } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { OPEN_ADD_EXISTING_SECTION_MODAL_BUS } from "@/composables/useOpenAddExistingSectionModalBus";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { HTMLTemplateResult, HTMLTemplateStringProcessor, LazyboxItem } from "@tuleap/lazybox";
import { createLazyAutocompleter } from "@tuleap/lazybox";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import type { LazyAutocompleter } from "@tuleap/lazybox/src/LazyAutocompleterElement";
import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { createSectionFromExistingArtifact } from "@/helpers/rest-querier";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { PositionForSection } from "@/sections/save/SectionsPositionsForSaveRetriever";
import { AT_THE_END } from "@/sections/insert/SectionsInserter";
import type { Artifact } from "@/helpers/search-existing-artifacts-for-autocompleter";
import {
    isArtifact,
    searchExistingArtifactsForAutocompleter,
} from "@/helpers/search-existing-artifacts-for-autocompleter";
import { errAsync } from "neverthrow";
import { getInsertionPositionExcludingPendingSections } from "@/helpers/get-insertion-position-excluding-pending-sections";
import { getSectionsNumberer } from "@/sections/levels/SectionsNumberer";

const gettext_provider = useGettext();
const { $gettext, interpolate } = gettext_provider;

const close_title = $gettext("Close");

const documentId = strictInject(DOCUMENT_ID);
const configuration = strictInject(CONFIGURATION_STORE);
const sections_collection = strictInject(SECTIONS_COLLECTION);
const sections_numberer = getSectionsNumberer(sections_collection);

const modal_element = ref<HTMLElement | undefined>(undefined);

const selected = ref<Artifact | null>(null);
const title_field = computed(() => configuration.selected_tracker.value?.title);
const is_search_allowed = computed(() => Boolean(title_field.value));
const is_submit_button_disabled = computed(
    () => is_search_allowed.value === false || selected.value === null,
);
const submit_button_icon = "fa-solid fa-plus";
const explanations = computed(() =>
    interpolate(
        $gettext("Search existing artifact to use as section inside tracker %{ tracker }"),
        {
            tracker: configuration.selected_tracker.value?.label,
        },
    ),
);
const title = computed(() =>
    configuration.selected_tracker.value
        ? interpolate($gettext("Import existing %{tracker_label}"), {
              tracker_label: configuration.selected_tracker.value.item_name,
          })
        : $gettext("Import existing section"),
);
const error_message = ref(
    is_search_allowed.value
        ? ""
        : interpolate(
              $gettext(
                  "There is no title field on the configured tracker %{ tracker } (or you cannot submit it), therefore you cannot search for artifacts to import.",
              ),
              {
                  tracker: configuration.selected_tracker.value?.label,
              },
          ),
);
const has_error_message = computed(() => error_message.value.length > 0);

const body = ref<HTMLElement>();
let autocompleter: (LazyAutocompleter & HTMLElement) | null = null;

const noop = (): void => {};

let on_successful_addition_callback: (section: ArtidocSection) => void = noop;
let add_position: PositionForSection = AT_THE_END;

strictInject(OPEN_ADD_EXISTING_SECTION_MODAL_BUS).registerHandler(openModal);

let modal: Modal | null = null;
function openModal(
    position: PositionForSection,
    on_successful_addition: (section: ArtidocSection) => void,
): void {
    add_position = position;
    on_successful_addition_callback = on_successful_addition;

    if (body.value) {
        autocompleter?.remove();

        autocompleter = createLazyAutocompleter(document);
        autocompleter.options = {
            placeholder: $gettext("Title..."),
            search_input_callback(query: string): void {
                error_message.value = "";
                selected.value = null;
                if (!configuration.selected_tracker.value) {
                    return;
                }

                if (!title_field.value) {
                    return;
                }

                if (!autocompleter) {
                    return;
                }

                searchExistingArtifactsForAutocompleter(
                    query,
                    autocompleter,
                    configuration.selected_tracker.value,
                    title_field.value,
                    sections_collection,
                    gettext_provider,
                ).orElse((fault) => {
                    error_message.value = String(fault);

                    return errAsync(fault);
                });
            },
            selection_callback(item: unknown): void {
                if (isArtifact(item)) {
                    selected.value = item;
                }
            },
            templating_callback(
                html: typeof HTMLTemplateStringProcessor,
                item: LazyboxItem,
            ): HTMLTemplateResult {
                if (!isArtifact(item.value)) {
                    return html``;
                }

                return html`<span
                        class="tlp-badge-rounded tlp-badge-outline tlp-badge-${item.value.tracker
                            .color_name}"
                        >${item.value.xref}</span
                    >
                    ${item.value.title}`;
            },
        };
        body.value.appendChild(autocompleter);
    }

    if (modal === null && modal_element.value) {
        modal = createModal(toRaw(modal_element.value));
    }

    if (modal) {
        modal.show();
    }
}

function closeModal(): void {
    if (modal) {
        modal.hide();
    }
}

function onSubmit(event: Event): void {
    event.preventDefault();
    if (!selected.value) {
        return;
    }

    const insertion_position_excluding_pending_sections =
        getInsertionPositionExcludingPendingSections(add_position, sections_collection);
    createSectionFromExistingArtifact(
        documentId,
        selected.value.id,
        insertion_position_excluding_pending_sections,
        sections_numberer.getLevelFromPositionOfImportedExistingSection(
            insertion_position_excluding_pending_sections,
        ),
    ).match(
        (section: ArtidocSection) => {
            on_successful_addition_callback(section);
            modal?.hide();
        },
        (fault) => {
            error_message.value = interpolate(
                $gettext(
                    "An error occurred while creating section from existing artifact %{ xref }: %{ details }",
                ),
                {
                    xref: selected.value?.xref,
                    details: String(fault),
                },
            );
        },
    );
}
</script>

<style lang="scss">
@use "pkg:@tuleap/lazybox";

.lazybox-dropdown-option-value-disabled,
.lazybox-dropdown-option-value {
    gap: var(--tlp-small-spacing);
    padding: calc(var(--tlp-form-element-padding-horizontal) / 2)
        var(--tlp-form-element-padding-horizontal);
}

.lazybox-single-search-section::part(input) {
    padding: 0 var(--tlp-form-element-padding-horizontal);
}
</style>
