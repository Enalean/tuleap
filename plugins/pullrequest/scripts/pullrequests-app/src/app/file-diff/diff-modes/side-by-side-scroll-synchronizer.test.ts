/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { Editor } from "codemirror";
import * as window_helper from "../../window-helper";
import { synchronize } from "./side-by-side-scroll-synchronizer";

type ProgrammaticallyScrollableEditor = Editor & {
    on: jest.SpyInstance;
    scrollTo: jest.SpyInstance;
    getScrollInfo: jest.SpyInstance;
    triggerScroll: () => void;
};

describe("scroll synchronizer", () => {
    describe("synchronize()", () => {
        let left_code_mirror: ProgrammaticallyScrollableEditor,
            right_code_mirror: ProgrammaticallyScrollableEditor,
            clearInterval: jest.SpyInstance,
            setInterval: jest.SpyInstance,
            timerHandler: () => void;
        beforeEach(() => {
            left_code_mirror = buildProgrammaticallyScrollableCodeMirror();
            right_code_mirror = buildProgrammaticallyScrollableCodeMirror();
            clearInterval = jest.spyOn(window_helper, "clearInterval");
            setInterval = jest
                .spyOn(window_helper, "setInterval")
                .mockImplementation((callback: () => void, time: number): number => {
                    timerHandler = callback;

                    return time;
                });
        });

        it("Given two code mirrors, when the left one is scrolling, then the right one will be aligned and the synchronizer state will switch to 1 to let a delay pass", () => {
            left_code_mirror.getScrollInfo.mockReturnValue({ left: 3, top: 99 });

            synchronize(left_code_mirror, right_code_mirror);
            left_code_mirror.triggerScroll();
            timerHandler();

            expect(right_code_mirror.scrollTo).toHaveBeenCalledWith(3, 99);
            expect(setInterval).toHaveBeenCalled();

            expect(clearInterval).not.toHaveBeenCalled();
        });

        it("Given a delay has passed, then the synchronizer state will switch to 2 to let another delay pass (it gives a smoother user experience)", () => {
            left_code_mirror.getScrollInfo.mockReturnValue({ left: 0, top: 457 });

            synchronize(left_code_mirror, right_code_mirror);
            left_code_mirror.triggerScroll();
            timerHandler();
            timerHandler();

            expect(right_code_mirror.scrollTo).toHaveBeenCalledWith(0, 457);

            expect(clearInterval).not.toHaveBeenCalled();
        });

        it("Given two delays have passed, then the timer will be released and the active_handler will be released", () => {
            left_code_mirror.getScrollInfo.mockReturnValue({ left: 0, top: 72 });

            synchronize(left_code_mirror, right_code_mirror);
            left_code_mirror.triggerScroll();
            timerHandler();
            timerHandler();
            timerHandler();

            expect(clearInterval).toHaveBeenCalled();
        });

        it("When the right code mirror is scrolling, then the left one will be aligned", () => {
            right_code_mirror.getScrollInfo.mockReturnValue({ left: 50, top: 82 });

            synchronize(left_code_mirror, right_code_mirror);
            right_code_mirror.triggerScroll();
            timerHandler();

            expect(left_code_mirror.scrollTo).toHaveBeenCalledWith(50, 82);
            expect(setInterval).toHaveBeenCalled();
        });

        it("Given the left code mirror is scrolling and the delay has not passed, when I also scroll the right one, then it will be ignored so that there is no infinite scroll loop", () => {
            left_code_mirror.getScrollInfo.mockReturnValue({ left: 0, top: 77 });

            synchronize(left_code_mirror, right_code_mirror);
            left_code_mirror.triggerScroll();
            right_code_mirror.triggerScroll();

            expect(right_code_mirror.scrollTo).toHaveBeenCalled();
            expect(left_code_mirror.scrollTo).not.toHaveBeenCalled();
        });
    });
});

function buildProgrammaticallyScrollableCodeMirror(): ProgrammaticallyScrollableEditor {
    const fake_code_mirror = {
        on: jest.fn(),
        scrollTo: jest.fn(),
        getScrollInfo: jest.fn(),
        triggerScroll: (): void => {
            // No trigger defined yet
        },
    };

    fake_code_mirror.on.mockImplementation((event_name, callback) => {
        fake_code_mirror.triggerScroll = callback;
    });

    return fake_code_mirror as unknown as ProgrammaticallyScrollableEditor;
}
