/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import { beforeEach, describe, expect, it } from "vitest";
import { ref } from "vue";
import type { Ref } from "vue";
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import SectionContainer from "@/components/section/SectionContainer.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import { SECTIONS_BELOW_ARTIFACTS } from "@/sections-below-artifacts-injection-key";
import { CURRENT_VERSION_DISPLAYED } from "@/components/current-version-displayed";
import type { Version } from "@/components/sidebar/versions/fake-list-of-versions";
import { noop } from "@/helpers/noop";

describe("SectionContainer", () => {
    let artidoc_section: ArtidocSection, old_version: Ref<Option<Version>>;
    beforeEach(() => {
        artidoc_section = ArtifactSectionFactory.create();
        old_version = ref(Option.nothing());
    });

    function getWrapper(is_bad: boolean = false): VueWrapper {
        const reactive_section = ReactiveStoredArtidocSectionStub.fromSection(artidoc_section);
        const sections_below_artifacts = is_bad ? [reactive_section.value.internal_id] : [];

        return shallowMount(SectionContainer, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [SECTIONS_BELOW_ARTIFACTS.valueOf()]: ref(sections_below_artifacts),
                    [CURRENT_VERSION_DISPLAYED.valueOf()]: {
                        old_version,
                        switchToOldVersion: noop,
                        switchToLatestVersion: noop,
                    },
                },
            },
            props: {
                section: reactive_section,
            },
        });
    }

    it("should use the color of the artifact tracker", () => {
        artidoc_section = ArtifactSectionFactory.withTrackerColor("red-wine");
        expect(getWrapper().classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-red-wine",
        ]);
    });

    it("should use the color of the pending artifact tracker", () => {
        artidoc_section = PendingArtifactSectionFactory.create();
        expect(getWrapper().classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-flamingo-pink",
        ]);
    });

    it("should not use the tlp-swatch palette if it is a skeleton", () => {
        artidoc_section = ArtifactSectionFactory.skeleton();
        expect(getWrapper().classes()).toStrictEqual(["artidoc-section-container"]);
    });

    it("should not use the tlp-swatch palette if it is a Freetext section", () => {
        artidoc_section = FreetextSectionFactory.create();
        expect(getWrapper().classes()).toStrictEqual(["artidoc-section-container"]);
    });

    it(`should show a class when it is below an artifact section (which is not allowed)`, () => {
        expect(getWrapper(true).classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-fiesta-red",
            "section-with-artifact-parent",
        ]);
    });

    it(`should not show a class when it is below an artifact section BUT a previous version is being displayed`, () => {
        old_version.value = Option.fromValue({} as Version);
        expect(getWrapper(true).classes()).not.toContain([
            "tlp-swatch-fiesta-red",
            "section-with-artifact-parent",
        ]);
    });

    it(`should add a class when it is an artifact section with fields`, () => {
        artidoc_section = ArtifactSectionFactory.override({
            fields: [
                {
                    type: "text",
                    label: "Label",
                    value: "Value",
                    display_type: "column",
                },
            ],
        });
        expect(getWrapper().classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-fiesta-red",
            "section-with-fields",
        ]);
    });
});
