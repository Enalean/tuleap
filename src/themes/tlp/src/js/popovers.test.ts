/*
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

import * as Popper from "popper.js";
import { createPopover, Popover, POPOVER_SHOWN_CLASS_NAME } from "./popovers";

jest.mock("popper.js", () => {
    return {
        __esModule: true,
        default: function (trigger_element: Element, content_element: Element): Popper.default {
            return ({
                popper: content_element,
                destroy: jest.fn(),
                scheduleUpdate: jest.fn(),
            } as unknown) as Popper.default;
        },
    };
});

describe(`Popovers`, () => {
    let trigger_element: HTMLElement, content_element: HTMLElement;
    let doc: Document;
    beforeEach(() => {
        doc = createLocalDocument();
        trigger_element = doc.createElement("span");
        content_element = doc.createElement("div");
        doc.body.append(trigger_element, content_element);
    });

    afterEach(() => {
        trigger_element.remove();
        content_element.remove();
    });

    describe(`constructor`, () => {
        let popperConstructor: jest.SpyInstance;
        beforeEach(() => {
            popperConstructor = jest.spyOn(Popper, "default");
        });

        it(`when there is an options.anchor,
            it will use it instead of the trigger element as an anchor in popper options`, () => {
            const anchor_element = doc.createElement("div");
            anchor_element.dataset.placement = "right";
            doc.body.append(anchor_element);

            const popover = createPopover(doc, trigger_element, content_element, {
                anchor: anchor_element,
            });
            const placement_option = popperConstructor.mock.calls[0][2].placement;
            expect(placement_option).toEqual("right");

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
            const placement_option = popperConstructor.mock.calls[0][2].placement;
            expect(placement_option).toEqual("left");

            popover.destroy();
        });

        it(`when the trigger element's data-placement attribute is invalid,
            it will throw`, () => {
            trigger_element.dataset.placement = "invalid";

            expect(() => createPopover(doc, trigger_element, content_element)).toThrow(
                "Invalid placement received: invalid."
            );
        });

        it(`when there is neither options.placement nor data-placement,
            it will default to "bottom" placement`, () => {
            const popover = createPopover(doc, trigger_element, content_element);
            const placement_option = popperConstructor.mock.calls[0][2].placement;
            expect(placement_option).toEqual("bottom");

            popover.destroy();
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
                const { first_content, second_content, cleanup } = createOtherShownPopoverContents(
                    doc
                );

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
                const { first_content, second_content, cleanup } = createOtherShownPopoverContents(
                    doc
                );

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
                    const {
                        first_content,
                        second_content,
                        cleanup,
                    } = createOtherShownPopoverContents(doc);

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
                    const {
                        first_content,
                        second_content,
                        cleanup,
                    } = createOtherShownPopoverContents(doc);

                    trigger_element.dispatchEvent(new MouseEvent("click"));
                    doc.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
                    expectThePopoverToBeHidden(first_content);
                    expectThePopoverToBeHidden(second_content);

                    cleanup();
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
