import testmanagement_module from "./app.js";
import angular from "angular";
import "angular-mocks";

describe("InPropertiesFilter", () => {
    var ngFilter;

    beforeEach(() => {
        angular.mock.module(testmanagement_module);
        angular.mock.inject(function ($filter) {
            ngFilter = $filter;
        });
    });

    var properties = ["label", "status"],
        list = [
            { id: 1, label: "Valid 7.11", status: "First status" },
            { id: 2, label: "Valid 7.11", status: "Second status" },
            { id: 3, label: "Valid 8", status: "First status" },
            { id: 4, label: "Valid 8 beta", status: "First status" },
            { id: 5, label: "Valid status", status: "First status" },
            { id: 6, label: "Valid 9", status: "Plop" },
        ];

    it("has a InPropertiesFilter filter", function () {
        expect(ngFilter("InPropertiesFilter")).not.toBeNull();
    });

    it("filters on campaign label", function () {
        expect(ngFilter("InPropertiesFilter")(list, "beta", properties)).toContainEqual({
            id: 4,
            label: "Valid 8 beta",
            status: "First status",
        });
    });

    it("filters on campaign status", function () {
        expect(ngFilter("InPropertiesFilter")(list, "First", properties)).toContainEqual({
            id: 1,
            label: "Valid 7.11",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "First", properties)).toContainEqual({
            id: 3,
            label: "Valid 8",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "First", properties)).toContainEqual({
            id: 4,
            label: "Valid 8 beta",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "First", properties)).toContainEqual({
            id: 5,
            label: "Valid status",
            status: "First status",
        });
    });

    it("filters on both status", function () {
        expect(ngFilter("InPropertiesFilter")(list, "status", properties)).toContainEqual({
            id: 1,
            label: "Valid 7.11",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "status", properties)).toContainEqual({
            id: 2,
            label: "Valid 7.11",
            status: "Second status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "status", properties)).toContainEqual({
            id: 3,
            label: "Valid 8",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "status", properties)).toContainEqual({
            id: 4,
            label: "Valid 8 beta",
            status: "First status",
        });
        expect(ngFilter("InPropertiesFilter")(list, "status", properties)).toContainEqual({
            id: 5,
            label: "Valid status",
            status: "First status",
        });
    });

    it("Given 'closed' keyword and given a campaign label containing 'closed', then it will not return duplicates", () => {
        const campaign = [{ id: 21, label: "Closed valid 10.4", status: "closed" }];
        expect(ngFilter("InPropertiesFilter")([campaign], "closed", properties)).toEqual([
            campaign,
        ]);
    });
});
