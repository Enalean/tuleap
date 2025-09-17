/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { describe, it, expect, beforeEach } from "vitest";
import { createGettext } from "vue3-gettext";
import ConfigurationModalTabs from "@/components/configuration/ConfigurationModalTabs.vue";
import type { ConfigurationTab } from "@/components/configuration/configuration-modal";
import {
    READONLY_FIELDS_SELECTION_TAB,
    TRACKER_SELECTION_TAB,
    EXPERIMENTAL_FEATURES_TAB,
} from "@/components/configuration/configuration-modal";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { SectionsStatesCollectionStub } from "@/sections/stubs/SectionsStatesCollectionStub";
import type { SectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";
import { CAN_USER_DISPLAY_VERSIONS } from "@/can-user-display-versions-injection-key";

describe("ConfigurationModalTabs", () => {
    let current_tab: ConfigurationTab,
        is_tracker_configured: boolean,
        can_user_display_versions: boolean,
        states_collection: SectionsStatesCollection;

    beforeEach(() => {
        current_tab = TRACKER_SELECTION_TAB;
        is_tracker_configured = true;
        can_user_display_versions = true;
        states_collection = SectionsStatesCollectionStub.build();
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(ConfigurationModalTabs, {
            props: { current_tab },
            global: {
                provide: {
                    [SECTIONS_STATES_COLLECTION.valueOf()]: states_collection,
                    [SELECTED_TRACKER.valueOf()]: is_tracker_configured
                        ? SelectedTrackerStub.build()
                        : SelectedTrackerStub.withNoTracker(),
                    [CAN_USER_DISPLAY_VERSIONS.valueOf()]: can_user_display_versions,
                },
                plugins: [createGettext({ silent: true })],
            },
        });

    it("When the current tab is the tracker selection tab, then it should be active", () => {
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=experimental-features-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
    });

    it("When the current tab is the fields selection tab, then it should be active", () => {
        current_tab = READONLY_FIELDS_SELECTION_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=experimental-features-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
    });

    it("When the current tab is the experimental features tab, then it should be active", () => {
        current_tab = EXPERIMENTAL_FEATURES_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=experimental-features-tab]").classes()).toContain(
            "tlp-tab-active",
        );
    });

    it("When admin decide that user cannot display versions, then experimental tab should not be displayed", () => {
        can_user_display_versions = false;
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();

        expect(wrapper.get("[data-test=tracker-selection-tab]").classes()).toContain(
            "tlp-tab-active",
        );
        expect(wrapper.get("[data-test=fields-selection-tab]").classes()).not.toContain(
            "tlp-tab-active",
        );
        expect(wrapper.find("[data-test=experimental-features-tab]").exists()).toBe(false);
    });

    it("When the user clicks the tracker selection tab, then it should emit a 'switch-configuration-tab' event", () => {
        current_tab = READONLY_FIELDS_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=tracker-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toStrictEqual([
            [TRACKER_SELECTION_TAB],
        ]);
    });

    it("When the user clicks the experimental features tab, then it should emit a 'switch-configuration-tab' event", () => {
        current_tab = READONLY_FIELDS_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=experimental-features-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toStrictEqual([
            [EXPERIMENTAL_FEATURES_TAB],
        ]);
    });

    it("Given that no tracker has been configured, When the user clicks the fields selection tab, then it should NOT emit a 'switch-configuration-tab' event", () => {
        is_tracker_configured = false;
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=fields-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toBeUndefined();
    });

    it("Given that a tracker has been configured, When the user clicks the fields selection tab, then it should emit a 'switch-configuration-tab' event", () => {
        is_tracker_configured = true;
        current_tab = TRACKER_SELECTION_TAB;

        const wrapper = getWrapper();
        wrapper.get("[data-test=fields-selection-tab]").trigger("click");

        expect(wrapper.emitted("switch-configuration-tab")).toStrictEqual([
            [READONLY_FIELDS_SELECTION_TAB],
        ]);
    });

    describe("Disabled fields selection tab", () => {
        it("When no tracker has been configured, then it should be disabled", () => {
            is_tracker_configured = false;
            current_tab = TRACKER_SELECTION_TAB;

            expect(
                getWrapper().get("[data-test=fields-selection-tab]").attributes("disabled"),
            ).toBeDefined();
        });

        it("When the document has unsaved content, then it should be disabled", () => {
            is_tracker_configured = true;
            current_tab = TRACKER_SELECTION_TAB;
            states_collection.createStateForSection(
                ReactiveStoredArtidocSectionStub.fromSection(FreetextSectionFactory.pending()),
            );

            expect(
                getWrapper().get("[data-test=fields-selection-tab]").attributes("disabled"),
            ).toBeDefined();
        });
    });
});
