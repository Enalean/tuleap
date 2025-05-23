/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import {
    postLockFile,
    deleteLockFile,
    postLockEmbedded,
    deleteLockEmbedded,
    postLockLink,
    deleteLockLink,
    postLockEmpty,
    deleteLockEmpty,
} from "./lock-rest-querier";
import type { Embedded, Empty, ItemFile, Link } from "../type";

describe("lock rest querier", () => {
    const id = 1234;

    it("Locks a file", async () => {
        const tlpPost = vi.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost);

        await postLockFile({ id: id } as ItemFile);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_files/${id}/lock`, {
            headers: { "content-type": "application/json" },
        });
    });

    it("Locks an embedded", async () => {
        const tlpPost = vi.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost);

        await postLockEmbedded({ id: id } as Embedded);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_embedded_files/${id}/lock`, {
            headers: { "content-type": "application/json" },
        });
    });

    it("Locks a link", async () => {
        const tlpPost = vi.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost);

        await postLockLink({ id: id } as Link);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_links/${id}/lock`, {
            headers: { "content-type": "application/json" },
        });
    });

    it("Locks an empty document", async () => {
        const tlpPost = vi.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost);

        await postLockEmpty({ id: id } as Empty);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_empty_documents/${id}/lock`, {
            headers: { "content-type": "application/json" },
        });
    });

    it("Unlocks a file", async () => {
        const tlpDel = vi.spyOn(tlp_fetch, "del");
        mockFetchSuccess(tlpDel);

        await deleteLockFile({ id: id } as ItemFile);

        expect(tlpDel).toHaveBeenCalledWith(`/api/docman_files/${id}/lock`);
    });

    it("Unlocks an embedded", async () => {
        const tlpDel = vi.spyOn(tlp_fetch, "del");
        mockFetchSuccess(tlpDel);

        await deleteLockEmbedded({ id: id } as Embedded);

        expect(tlpDel).toHaveBeenCalledWith(`/api/docman_embedded_files/${id}/lock`);
    });

    it("Unlocks a link", async () => {
        const tlpDel = vi.spyOn(tlp_fetch, "del");
        mockFetchSuccess(tlpDel);

        await deleteLockLink({ id: id } as Link);

        expect(tlpDel).toHaveBeenCalledWith(`/api/docman_links/${id}/lock`);
    });

    it("Unlocks an empty document", async () => {
        const tlpDel = vi.spyOn(tlp_fetch, "del");
        mockFetchSuccess(tlpDel);

        await deleteLockEmpty({ id: id } as Empty);

        expect(tlpDel).toHaveBeenCalledWith(`/api/docman_empty_documents/${id}/lock`);
    });
});
