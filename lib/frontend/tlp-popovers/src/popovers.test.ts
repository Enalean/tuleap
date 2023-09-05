/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { SpyInstance } from "vitest";
import { describe, it, beforeEach, afterEach, expect, vi } from "vitest";

import type { Popover } from "./popovers";
import { createPopover, POPOVER_SHOWN_CLASS_NAME } from "./popovers";
import * as floating_ui from "@floating-ui/dom";
import type { ComputePositionConfig, ComputePositionReturn } from "@floating-ui/dom";

vi.mock("@floating-ui/dom", async () => {
    const actual_floating_ui_dom: typeof floating_ui = await vi.importActual("@floating-ui/dom");
    return {
        ...actual_floating_ui_dom,
        autoUpdate: vi.fn(),
        computePosition: vi.fn(),
    };
});

describe(`Popovers`, () => {
    let trigger_element: HTMLElement, content_element: HTMLElement;
    let doc: Document;
    let cleanup: () => void;
    let computePosition: SpyInstance;

    beforeEach(() => {
        doc = createLocalDocument();
        trigger_element = doc.createElement("span");
        content_element = doc.createElement("div");
        doc.body.append(trigger_element, content_element);
        cleanup = vi.fn();
        const auto_update_spy = floating_ui.autoUpdate as unknown as SpyInstance;
        auto_update_spy.mockReturnValue(cleanup);
        computePosition = floating_ui.computePosition as unknown as SpyInstance;
        computePosition.mockResolvedValue({
            x: 10,
            y: 20,
            placement: "top",
        } as ComputePositionReturn);
    });

    afterEach(() => {
        trigger_element.remove();
        content_element.remove();
        vi.clearAllMocks();
    });

    describe(`configuration`, () => {
        it(`when there is an options.anchor,
            it will use it instead of the trigger element as an anchor in popper options`, () => {
            const anchor_element = doc.createElement("div");
            anchor_element.dataset.placement = "right";
            doc.body.append(anchor_element);

            const popover = createPopover(doc, trigger_element, content_element, {
                anchor: anchor_element,
            });
            trigger_element.dispatchEvent(new MouseEvent("mouseover"));
            expectThePopoverToBeShown(content_element);

            expect(computePosition).toHaveBeenCalledWith(
                anchor_element,
                content_element,
                expect.objectContaining({
                    placement: "right",
                }),
            );

            popover.destroy();
            anchor_element.remove();
        });

        it(`when there is no options.trigger,
            it will use the trigger element's data-trigger attribute`, () => {
            trigger_element.dataset.trigger = "click";
            const popover = createPopover(doc, trigger_element, content_element);

            trigger_element.dispatchEvent(new MouseEvent("click"));
            expectThePopoverToBeShown(content_element);

            popover.destroy();
        });

        it(`when there is neither options.trigger nor data-trigger,
            it will default to "hover" trigger`, () => {
            const popover = createPopover(doc, trigger_element, content_element);

            trigger_element.dispatchEvent(new MouseEvent("mouseover"));
            expectThePopoverToBeShown(content_element);

            popover.destroy();
        });

        it(`when there is no options.placement,
            it will use the trigger element's data-placement attribute`, () => {
            trigger_element.dataset.placement = "left";

            const popover = createPopover(doc, trigger_element, content_element);
            trigger_element.dispatchEvent(new MouseEvent("mouseover"));
            expectThePopoverToBeShown(content_element);

            expect(computePosition).toHaveBeenCalledWith(
                trigger_element,
                content_element,
                expect.objectContaining({
                    placement: "left",
                }),
            );

            popover.destroy();
        });

        it(`when the trigger element's data-placement attribute is invalid,
            it will throw`, () => {
            trigger_element.dataset.placement = "invalid";

            expect(() => createPopover(doc, trigger_element, content_element)).toThrow(
                "Invalid placement received: invalid.",
            );
        });

        it(`when there is neither options.placement nor data-placement,
            it will default to "bottom" placement`, () => {
            const popover = createPopover(doc, trigger_element, content_element);
            trigger_element.dispatchEvent(new MouseEvent("mouseover"));
            expectThePopoverToBeShown(content_element);

            expect(computePosition).toHaveBeenCalledWith(
                trigger_element,
                content_element,
                expect.objectContaining({
                    placement: "bottom",
                }),
            );

            popover.destroy();
        });
    });

    describe("middleware", () => {
        it("should provide flip() middleware to ensure the content is not offscreen", () => {
            return new Promise((done) => {
                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const flip_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "flip",
                        );
                        if (!flip_middleware) {
                            done(Error("No flip middleware"));
                            return Promise.resolve();
                        }

                        expect(flip_middleware.options).toStrictEqual({});
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element);
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });

        it("should accept options for flip() middleware", () => {
            return new Promise((done) => {
                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const flip_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "flip",
                        );
                        if (!flip_middleware) {
                            done(Error("No flip middleware"));
                            return Promise.resolve();
                        }

                        expect(flip_middleware.options).toStrictEqual({
                            fallbackPlacements: ["right"],
                        });
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element, {
                    middleware: {
                        flip: {
                            fallbackPlacements: ["right"],
                        },
                        offset: {},
                    },
                });
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });

        it("should provide offset() middleware to ensure the content is not glued to the anchor", () => {
            return new Promise((done) => {
                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const offset_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "offset",
                        );
                        if (!offset_middleware) {
                            done(Error("No offset middleware"));
                            return Promise.resolve();
                        }

                        expect(offset_middleware.options).toStrictEqual({
                            mainAxis: 10,
                            alignmentAxis: -15,
                        });
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element);
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });

        it("should accept options for offset() middleware", () => {
            return new Promise((done) => {
                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const offset_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "offset",
                        );
                        if (!offset_middleware) {
                            done(Error("No offset middleware"));
                            return Promise.resolve();
                        }

                        expect(offset_middleware.options).toStrictEqual({
                            mainAxis: 10,
                            alignmentAxis: 666,
                        });
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element, {
                    middleware: {
                        flip: {},
                        offset: { alignmentAxis: 666 },
                    },
                });
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });

        it("should provide shift() middleware to ensure the content is not glued to the border of the viewport", () => {
            return new Promise((done) => {
                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const shift_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "shift",
                        );
                        if (!shift_middleware) {
                            done(Error("No shift middleware"));
                            return Promise.resolve();
                        }

                        expect(shift_middleware.options).toStrictEqual({
                            padding: 16,
                        });
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element);
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });

        it(`when the popover content has an arrow
            it should add a corresponding middleware so that the arrow is nicely aligned`, () => {
            return new Promise((done) => {
                const arrow_element = doc.createElement("div");
                arrow_element.classList.add("tlp-popover-arrow");
                content_element.appendChild(arrow_element);

                computePosition.mockImplementation(
                    (
                        trigger,
                        content,
                        options: ComputePositionConfig,
                    ): Promise<ComputePositionReturn | void> => {
                        if (!options.middleware) {
                            done(Error("No middleware given"));
                            return Promise.resolve();
                        }

                        const arrow_middleware = options.middleware.find(
                            (middleware) => middleware && middleware.name === "arrow",
                        );
                        if (!arrow_middleware) {
                            done(Error("No arrow middleware"));
                            return Promise.resolve();
                        }

                        expect(arrow_middleware.options).toStrictEqual({
                            element: arrow_element,
                            padding: 15,
                        });
                        done(Promise.resolve());

                        return Promise.resolve({
                            x: 10,
                            y: 20,
                            placement: "top",
                        } as ComputePositionReturn);
                    },
                );

                const popover = createPopover(doc, trigger_element, content_element);
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));

                popover.destroy();
            });
        });
    });

    describe(`hide()`, () => {
        it(`when I programmatically hide the popover, it will hide it and cleanup autoupdate`, () => {
            const popover = createPopover(doc, trigger_element, content_element);
            content_element.classList.add(POPOVER_SHOWN_CLASS_NAME);

            popover.hide();

            expect(content_element.classList.contains(POPOVER_SHOWN_CLASS_NAME)).toBe(false);
            expect(cleanup).toHaveBeenCalled();
        });
    });

    describe(`with hover trigger`, () => {
        let popover: Popover;
        beforeEach(() => {
            popover = createPopover(doc, trigger_element, content_element);
        });

        afterEach(() => {
            popover.destroy();
        });

        describe(`when I hover my mouse over the trigger element`, () => {
            it(`will show the popover`, () => {
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));
                expectThePopoverToBeShown(content_element);
            });

            it(`will hide all shown popovers`, () => {
                const { first_content, second_content, cleanup } =
                    createOtherShownPopoverContents(doc);

                trigger_element.dispatchEvent(new MouseEvent("mouseover"));
                expectThePopoverToBeHidden(first_content);
                expectThePopoverToBeHidden(second_content);

                cleanup();
            });
        });

        describe(`when I move my mouse out of the trigger element`, () => {
            beforeEach(() => {
                trigger_element.dispatchEvent(new MouseEvent("mouseover"));
            });

            it(`will hide the popover"`, () => {
                trigger_element.dispatchEvent(new MouseEvent("mouseout"));
                expectThePopoverToBeHidden(content_element);
            });

            it(`will hide all shown popovers`, () => {
                const { first_content, second_content, cleanup } =
                    createOtherShownPopoverContents(doc);

                trigger_element.dispatchEvent(new MouseEvent("mouseout"));
                expectThePopoverToBeHidden(first_content);
                expectThePopoverToBeHidden(second_content);

                cleanup();
            });
        });
    });

    describe(`with click trigger`, () => {
        describe(`without dismiss buttons`, () => {
            let popover: Popover;
            beforeEach(() => {
                popover = createPopover(doc, trigger_element, content_element, {
                    trigger: "click",
                });
            });

            afterEach(() => {
                popover.destroy();
            });

            describe(`when I click on the trigger element`, () => {
                it(`when the popover is not already shown, it will show it`, () => {
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    expectThePopoverToBeShown(content_element);
                });

                it(`when the popover is already shown, it will hide it`, () => {
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    expectThePopoverToBeHidden(content_element);
                });

                it(`will hide all shown popovers`, () => {
                    const { first_content, second_content, cleanup } =
                        createOtherShownPopoverContents(doc);

                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    expectThePopoverToBeHidden(first_content);
                    expectThePopoverToBeHidden(second_content);

                    cleanup();
                });
            });

            describe(`when I click outside of the popover`, () => {
                it(`and it is not shown, nothing happens`, () => {
                    doc.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
                    expectThePopoverToBeHidden(content_element);
                });

                it(`and it is shown, it will hide it`, () => {
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    doc.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
                    expectThePopoverToBeHidden(content_element);
                });

                it(`and it is shown, it will hide all shown popovers`, () => {
                    const { first_content, second_content, cleanup } =
                        createOtherShownPopoverContents(doc);

                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    doc.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
                    expectThePopoverToBeHidden(first_content);
                    expectThePopoverToBeHidden(second_content);

                    cleanup();
                });
            });

            describe("when I hit the `Escape` key", () => {
                it("does not do anything when hitting the `Escape` key if the popover is already closed", () => {
                    doc.body.dispatchEvent(
                        new KeyboardEvent("keyup", { key: "Escape", bubbles: true }),
                    );
                    expectThePopoverToBeHidden(content_element);
                });

                it("hides the popover when hitting the `Escape` key", () => {
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    doc.body.dispatchEvent(
                        new KeyboardEvent("keyup", { key: "Escape", bubbles: true }),
                    );
                    expectThePopoverToBeHidden(content_element);
                });

                it("will not hide the popover if hitting a key other than `Escape`", () => {
                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    doc.body.dispatchEvent(new KeyboardEvent("keyup", { key: "a", bubbles: true }));
                    expectThePopoverToBeShown(content_element);
                });
            });
        });

        it(`when I click on a [data-dismiss=popover] element,
        it will hide all shown popovers`, () => {
            const dismiss = doc.createElement("button");
            dismiss.dataset.dismiss = "popover";
            content_element.append(dismiss);
            const { first_content, second_content, cleanup } = createOtherShownPopoverContents(doc);
            const popover = createPopover(doc, trigger_element, content_element, {
                trigger: "click",
            });

            dismiss.dispatchEvent(new MouseEvent("click"));
            expectThePopoverToBeHidden(first_content);
            expectThePopoverToBeHidden(second_content);

            popover.destroy();
            cleanup();
            dismiss.remove();
        });
    });
});

function createLocalDocument(): Document {
    return document.implementation.createHTMLDocument();
}

function expectThePopoverToBeShown(content_element: HTMLElement): void {
    expect(content_element.classList.contains(POPOVER_SHOWN_CLASS_NAME)).toBe(true);
}

function expectThePopoverToBeHidden(content_element: HTMLElement): void {
    expect(content_element.classList.contains(POPOVER_SHOWN_CLASS_NAME)).toBe(false);
}

interface OtherShownPopovers {
    first_content: HTMLElement;
    second_content: HTMLElement;
    cleanup: () => void;
}

function createOtherShownPopoverContents(doc: Document): OtherShownPopovers {
    const first_content = doc.createElement("div");
    const second_content = doc.createElement("div");
    first_content.classList.add(POPOVER_SHOWN_CLASS_NAME);
    second_content.classList.add(POPOVER_SHOWN_CLASS_NAME);
    doc.body.append(first_content, second_content);

    const cleanup = (): void => {
        first_content.remove();
        second_content.remove();
    };
    return { first_content, second_content, cleanup };
}
