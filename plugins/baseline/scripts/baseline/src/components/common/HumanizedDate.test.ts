/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import HumanizedDate from "./HumanizedDate.vue";
import type { Wrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import { createLocalVueForTests } from "../../support/local-vue";
import DateFormatter from "../../support/date-utils";
import moment from "moment";
import "moment/locale/fr";

describe("HumanizedDate", () => {
    let wrapper: Wrapper<Vue>;

    const now = moment("2019/02/23 09:37:20 +0001", "YYYY/MM/DD HH:mm:ss Z").toDate();

    beforeEach(async () => {
        jest.spyOn(Date, "now").mockReturnValue(now.getTime());

        DateFormatter.setOptions({
            user_locale: "fr_FR",
            user_timezone: "Europe/Paris",
            format: "d/m/Y H:i",
        });

        wrapper = mount(HumanizedDate, {
            propsData: { date: "2019-03-22T10:01:48+00:00" },
            localVue: await createLocalVueForTests(),
        });
    });

    it("shows date with human readable format", () => {
        expect(wrapper.text()).toBe("dans un mois");
    });

    describe("with capital at first character", () => {
        beforeEach(() => wrapper.setProps({ start_with_capital: true }));

        it("starts with capital", () => {
            expect(wrapper.text().charAt(0)).toBe("D");
        });
    });
});
