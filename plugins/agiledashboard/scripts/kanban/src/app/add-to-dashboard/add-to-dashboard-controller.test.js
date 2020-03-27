import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import AddToDashboardBaseController from "./add-to-dashboard-controller.js";

describe("AddToDashboardController -", () => {
    let AddToDashboardCtrl, SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        let $controller, $element;

        angular.mock.inject(function (_$controller_, _SharedPropertiesService_) {
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        $element = angular.element("div");
        AddToDashboardCtrl = $controller(AddToDashboardBaseController, {
            $element: $element,
            SharedPropertiesService: SharedPropertiesService,
        });
        AddToDashboardCtrl.dashboard_dropdown = {
            project_dashboards: [],
            user_dashboards: [],
        };
    });

    describe("showDashboardButton() -", () => {
        it("displays the button for a non project admin because the user has two personal dashboards", () => {
            AddToDashboardCtrl.dashboard_dropdown.user_dashboards = [
                {
                    id: "10",
                    name: "dashboard 1",
                },
                {
                    id: "11",
                    name: "dashboard 2",
                },
            ];
            jest.spyOn(SharedPropertiesService, "getUserIsAdmin").mockReturnValue(false);
            jest.spyOn(SharedPropertiesService, "getUserIsOnWidget").mockReturnValue(false);

            const showButton = AddToDashboardCtrl.showDashboardButton();
            expect(showButton).toBe(true);
        });

        it("displays the button for a project admin because the user has at least two project dashboards", () => {
            AddToDashboardCtrl.dashboard_dropdown.project_dashboards = [
                {
                    id: "20",
                    name: "project dashboard 1",
                },
                {
                    id: "21",
                    name: "project dashboard 2",
                },
            ];
            jest.spyOn(SharedPropertiesService, "getUserIsAdmin").mockReturnValue(true);
            jest.spyOn(SharedPropertiesService, "getUserIsOnWidget").mockReturnValue(false);

            const showButton = AddToDashboardCtrl.showDashboardButton();
            expect(showButton).toBe(true);
        });

        it("displays the button for a project admin because the user admin has at least two personal dashboards", () => {
            AddToDashboardCtrl.dashboard_dropdown.user_dashboards = [
                {
                    id: "10",
                    name: "dashboard 1",
                },
                {
                    id: "11",
                    name: "dashboard 2",
                },
            ];
            jest.spyOn(SharedPropertiesService, "getUserIsAdmin").mockReturnValue(true);
            jest.spyOn(SharedPropertiesService, "getUserIsOnWidget").mockReturnValue(false);

            const showButton = AddToDashboardCtrl.showDashboardButton();
            expect(showButton).toBe(true);
        });

        it("does not display the button for an basic user because the user has no personal dashboard even there is project dasboard", () => {
            AddToDashboardCtrl.dashboard_dropdown.project_dashboards = [
                {
                    id: "20",
                    name: "project dashboard 1",
                },
                {
                    id: "21",
                    name: "project dashboard 2",
                },
            ];
            jest.spyOn(SharedPropertiesService, "getUserIsAdmin").mockReturnValue(false);
            jest.spyOn(SharedPropertiesService, "getUserIsOnWidget").mockReturnValue(false);

            const showButton = AddToDashboardCtrl.showDashboardButton();
            expect(showButton).toBe(false);
        });

        it("does not display the button for a user project admin because the user has no personal dashboard and no project dashboard", () => {
            jest.spyOn(SharedPropertiesService, "getUserIsAdmin").mockReturnValue(true);
            jest.spyOn(SharedPropertiesService, "getUserIsOnWidget").mockReturnValue(false);

            const showButton = AddToDashboardCtrl.showDashboardButton();
            expect(showButton).toBe(false);
        });
    });
});
