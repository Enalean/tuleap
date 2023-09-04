import execution_module from "./execution.js";
import angular from "angular";
import ui_router from "@uirouter/angularjs";
import "angular-mocks";

describe("ExecutionListFilter", function () {
    var ngFilter;

    beforeEach(angular.mock.module(ui_router));
    beforeEach(angular.mock.module(execution_module));
    beforeEach(
        angular.mock.inject(function ($filter) {
            ngFilter = $filter;
        }),
    );

    var list = [
        {
            id: 24605,
            uri: "executions/24605",
            results: "",
            status: "passed",
            last_update_date: null,
            assigned_to: null,
            //...
            definition: {
                id: 24600,
                uri: "testdef/24600",
                summary: "Tracker Rule date verifications for a workflow",
                category: "AgileDashboard",
            },
        },
        {
            id: 24606,
            uri: "executions/24606",
            results: "",
            status: "failed",
            last_update_date: null,
            assigned_to: {
                id: 101,
                uri: "users/101",
                email: "renelataupe@example.com",
                real_name: "rtaupe",
                username: "rtaupe",
                ldap_id: "",
                avatar_url: "https://paelut/users/rtaupe/avatar.png",
            },
            //...
            definition: {
                id: 24601,
                uri: "testdef/24601",
                summary: "Html notification for tracker v5",
                category: "SOAP",
            },
        },
        {
            id: 24607,
            uri: "executions/24607",
            results: "",
            status: "passed",
            last_update_date: null,
            assigned_to: {
                id: 102,
                uri: "users/102",
                email: "joelclodo@example.com",
                real_name: "jclodo",
                username: "jclodo",
                ldap_id: "",
                avatar_url: "https://paelut/users/jclodo/avatar.png",
            },
            //…
            definition: {
                id: 24602,
                uri: "testdef/24602",
                summary: "Git test",
                category: "GIT",
            },
        },
    ];

    it("has a CampaignListFilter filter", function () {
        expect(ngFilter("ExecutionListFilter")).not.toBeNull();
    });

    it("filters on category", function () {
        var results = ngFilter("ExecutionListFilter")(list, "soap", {}, null);
        expect(results).toHaveLength(1);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24606 }));
    });

    it("filters on summary", function () {
        var results = ngFilter("ExecutionListFilter")(list, "workflow", {}, null);
        expect(results).toHaveLength(1);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24605 }));
    });

    it("filters on test def id", function () {
        var results = ngFilter("ExecutionListFilter")(list, "24601", {}, null);
        expect(results).toHaveLength(1);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24606 }));
    });

    it("filters on execution status", function () {
        var results = ngFilter("ExecutionListFilter")(list, "", { passed: true }, null);
        expect(results).toHaveLength(2);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24605 }));
    });

    it("filters on execution multiple status", function () {
        var results = ngFilter("ExecutionListFilter")(
            list,
            "",
            { passed: true, failed: true },
            null,
        );
        expect(results).toHaveLength(3);
        expect(results[0]).toEqual(expect.objectContaining({ id: 24605 }));
        expect(results[1]).toEqual(expect.objectContaining({ id: 24606 }));
        expect(results[2]).toEqual(expect.objectContaining({ id: 24607 }));
    });

    it("filters on summary and execution status", function () {
        var results = ngFilter("ExecutionListFilter")(list, "tracker", { passed: true }, null);
        expect(results).toHaveLength(1);
    });

    it("filters all tests when all filters are disabled", function () {
        const result = ngFilter("ExecutionListFilter")(
            list,
            "",
            { passed: false, blocked: false, failed: false, notrun: false },
            null,
        );

        expect(result).toHaveLength(0);
    });
});
