/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import { CrossReferenceHTMLElementDetector } from "./CrossReferenceNodeDetector";
import type { DetectCrossReferenceHTMLElement } from "./CrossReferenceNodeDetector";

describe("CrossReferenceNodeDetector", () => {
    let detector: DetectCrossReferenceHTMLElement, element: HTMLElement;

    beforeEach(() => {
        const doc = createLocalDocument();
        detector = CrossReferenceHTMLElementDetector();
        element = doc.createElement("span");
    });

    it("Given a HTMLElement with no data-href attribute, then it should return false", () => {
        expect(detector.isCrossReferenceHTMLElement(element)).toBe(false);
    });

    it("Given a HTMLElement with a data-href attribute, When it contains an empty string, then it should return false", () => {
        element.setAttribute("data-href", "");

        expect(detector.isCrossReferenceHTMLElement(element)).toBe(false);
    });

    it("Given a HTMLElement with a data-href attribute, When it contains a not empty string, then it should return true", () => {
        element.setAttribute("data-href", "https://example.com/");

        expect(detector.isCrossReferenceHTMLElement(element)).toBe(true);
    });
});
