/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import SoloSwimlane from "./SoloSwimlane.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { ColumnDefinition, Swimlane } from "../../../../type";

describe("SoloSwimlane", () => {
    it("displays the parent card in its own cell when status is null", () => {
        const wrapper = shallowMount(SoloSwimlane, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { id: 2, label: "To do", is_collapsed: false } as ColumnDefinition,
                            { id: 3, label: "Done", is_collapsed: false } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                swimlane: {
                    card: {
                        id: 43,
                        mapped_list_value: null
                    }
                } as Swimlane
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the parent card in its own cell when tracker id is not part of columns mapping", () => {
        const wrapper = shallowMount(SoloSwimlane, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [{ tracker_id: 42 }, { tracker_id: 43 }],
                                is_collapsed: false
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [{ tracker_id: 42 }, { tracker_id: 43 }],
                                is_collapsed: false
                            } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                swimlane: {
                    card: {
                        id: 43,
                        tracker_id: 666,
                        mapped_list_value: {
                            id: 1001,
                            label: "Fixed"
                        }
                    }
                } as Swimlane
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the parent card in Done column when status maps this column", () => {
        const wrapper = shallowMount(SoloSwimlane, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1002 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1003 }]
                                    }
                                ],
                                is_collapsed: false
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1000 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1001 }]
                                    }
                                ],
                                is_collapsed: false
                            } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                swimlane: {
                    card: {
                        id: 43,
                        tracker_id: 43,
                        mapped_list_value: {
                            id: 1001,
                            label: "Fixed"
                        }
                    }
                } as Swimlane
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it(`Given the parent card is in Done column
        and status maps this column
        and column is collapsed
        then swimlane is not displayed at all`, () => {
        const wrapper = shallowMount(SoloSwimlane, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1002 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1003 }]
                                    }
                                ],
                                is_collapsed: false
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1000 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1001 }]
                                    }
                                ],
                                is_collapsed: true
                            } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                swimlane: {
                    card: {
                        id: 43,
                        tracker_id: 43,
                        mapped_list_value: {
                            id: 1001,
                            label: "Fixed"
                        }
                    }
                } as Swimlane
            }
        });

        expect(wrapper.isEmpty()).toBe(true);
    });
});
