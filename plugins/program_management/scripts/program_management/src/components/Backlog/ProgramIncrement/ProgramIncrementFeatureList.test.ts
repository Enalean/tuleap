import * as retriever from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import type { Feature } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import * as configuration from "../../../configuration";
import { shallowMount } from "@vue/test-utils";
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";

describe("ProgramIncrementFeatureList", () => {
    it("Displays the empty state when no features are found", async () => {
        jest.spyOn(retriever, "getFeatures").mockResolvedValue([]);
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);

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
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);
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
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);

        const wrapper = shallowMount(ProgramIncrementFeatureList, {
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ProgramIncrementFeatureList> {
                return {
                    features: [element_one, element_two],
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
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe("true");
    });

    it("Does not have the can-plan attribute when user can not plan elements", async () => {
        jest.spyOn(retriever, "getFeatures").mockResolvedValue([]);
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);

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
        });

        expect(
            wrapper.get("[data-test=program-increment-feature-list]").element.dataset.canPlan
        ).toBe(undefined);
    });
});
