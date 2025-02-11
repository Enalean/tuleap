/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { LazyboxItem } from "@tuleap/lazybox";
import type { VerifyIsAlreadyLinked } from "../../../domain/links/VerifyIsAlreadyLinked";
import { VerifyIsAlreadyLinkedStub } from "../../../../tests/stubs/links/VerifyIsAlreadyLinkedStub";
import { LinkSelectorItemProxy } from "./LinkSelectorItemProxy";
import { LinkableArtifactStub } from "../../../../tests/stubs/links/LinkableArtifactStub";
import type { LinkableArtifact } from "../../../domain/links/LinkableArtifact";

describe(`LinkSelectorItemProxy`, () => {
    let link_verifier: VerifyIsAlreadyLinked, linkable_artifact: LinkableArtifact;

    beforeEach(() => {
        link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
        linkable_artifact = LinkableArtifactStub.withDefaults();
    });

    const build = (): LazyboxItem =>
        LinkSelectorItemProxy.fromLinkableArtifact(link_verifier, linkable_artifact);

    it(`builds from a linkable artifact`, () => {
        const item = build();
        expect(item.value).toBe(linkable_artifact);
        expect(item.is_disabled).toBe(false);
    });

    it(`builds a disabled item when the given artifact has already been linked once`, () => {
        link_verifier = VerifyIsAlreadyLinkedStub.withAllArtifactsAlreadyLinked();
        const item = build();
        expect(item.value).toBe(linkable_artifact);
        expect(item.is_disabled).toBe(true);
    });
});
