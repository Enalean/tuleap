import * as retriever from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import type { Feature } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import { shallowMount } from "@vue/test-utils";
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("ProgramIncrementFeatureList", () => {
    it("Displays the empty state when no features are found", async () => {
        jest.spyOn(retriever, "getFeatures").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe("true");
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(retriever, "getFeatures").mockResolvedValue([]);
        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe("true");
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            artifact_id: 1,
            artifact_title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as Feature;
        const element_two = {
            artifact_id: 2,
            artifact_title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as Feature;

        jest.spyOn(retriever, "getFeatures").mockResolvedValue([element_one, element_two]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest
                            .fn()
                            .mockReturnValue([element_one, element_two]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe("true");
    });

    it("Retrieve elements to display and store them in store", async () => {
        const element_one = {
            artifact_id: 1,
            artifact_title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as Feature;
        const element_two = {
            artifact_id: 2,
            artifact_title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as Feature;

        jest.spyOn(retriever, "getFeatures").mockImplementation(() =>
            Promise.resolve([element_one, element_two])
        );

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest
                            .fn()
                            .mockReturnValue([element_one, element_two]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(false),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("addProgramIncrement", {
            id: 1,
            title: "PI 1",
            status: "On going",
            start_date: "2020 Feb 6",
            end_date: "2020 Feb 28",
            user_can_plan: true,
            features: [element_one, element_two],
        });
    });

    it("Does not have the can-plan attribute when user can not plan elements", async () => {
        jest.spyOn(retriever, "getFeatures").mockResolvedValue([]);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [],
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
            propsData: {
                increment: {
                    id: 1,
                    title: "PI 1",
                    status: "On going",
                    start_date: "2020 Feb 6",
                    end_date: "2020 Feb 28",
                    user_can_plan: false,
                } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    getters: {
                        getFeaturesInProgramIncrement: jest.fn().mockReturnValue([]),
                        isProgramIncrementAlreadyAdded: jest.fn().mockReturnValue(true),
                    },
                    state: {
                        configuration: { program_id: 202 },
                    },
                }),
            },
        });

        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe(undefined);
    });
});
