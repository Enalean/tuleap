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
import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import SectionContainer from "@/components/section/SectionContainer.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";

describe("SectionContainer", () => {
    it("should use the color of the artifact tracker", () => {
        const wrapper = shallowMount(SectionContainer, {
            props: {
                section: ReactiveStoredArtidocSectionStub.fromSection(
                    ArtifactSectionFactory.create(),
                ),
            },
        });

        expect(wrapper.classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-fiesta-red",
        ]);
    });

    it("should use the color of the pending artifact tracker", () => {
        const wrapper = shallowMount(SectionContainer, {
            props: {
                section: ReactiveStoredArtidocSectionStub.fromSection(
                    PendingArtifactSectionFactory.create(),
                ),
            },
        });

        expect(wrapper.classes()).toStrictEqual([
            "artidoc-section-container",
            "tlp-swatch-flamingo-pink",
        ]);
    });

    it("should not use the tlp-swatch palette if it is not an artifact section", () => {
        const wrapper = shallowMount(SectionContainer, {
            props: {
                section: ReactiveStoredArtidocSectionStub.fromSection({} as ArtidocSection),
            },
        });

        expect(wrapper.classes()).toStrictEqual([
            "artidoc-section-container",
            "artidoc-section-container-without-border",
        ]);
    });

    it("should not use the tlp-swatch palette if it is a skeleton", () => {
        const wrapper = shallowMount(SectionContainer, {
            props: {
                section: ReactiveStoredArtidocSectionStub.fromSection(
                    ArtifactSectionFactory.skeleton(),
                ),
            },
        });

        expect(wrapper.classes()).toStrictEqual([
            "artidoc-section-container",
            "artidoc-section-container-without-border",
        ]);
    });

    it("should not use the tlp-swatch palette if it is a Freetext section", () => {
        const wrapper = shallowMount(SectionContainer, {
            props: {
                section: ReactiveStoredArtidocSectionStub.fromSection(
                    FreetextSectionFactory.create(),
                ),
            },
        });

        expect(wrapper.classes()).toStrictEqual([
            "artidoc-section-container",
            "artidoc-section-container-without-border",
        ]);
    });
});
