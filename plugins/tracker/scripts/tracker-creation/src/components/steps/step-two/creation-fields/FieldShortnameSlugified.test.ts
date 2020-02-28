import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../../helpers/local-vue-for-tests";
import FieldShortnameSlugified from "./FieldShortnameSlugified.vue";

describe("FieldShortnameSlugified", () => {
    async function getWrapper(): Promise<Wrapper<FieldShortnameSlugified>> {
        return shallowMount(FieldShortnameSlugified, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        tracker_to_be_created: {
                            name: "Kanban in the trees",
                            shortname: "kanban_in_the_trees"
                        }
                    }
                })
            },
            localVue: await createTrackerCreationLocalVue()
        });
    }

    it("toggles the manual mode when the user clicks on the shortname", async () => {
        const wrapper = await getWrapper();
        wrapper.trigger("click");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSlugifyShortnameMode", false);
    });
});
