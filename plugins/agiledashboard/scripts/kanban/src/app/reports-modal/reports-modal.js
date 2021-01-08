import { service, directive, template } from "@tuleap/angular-async";
import reports_template from "./reports-modal.tpl.html";

import GraphDirective from "./diagram-directive.js";
import DiagramRestService from "./diagram-rest-service.js";
import ReportsModalController from "./reports-modal-controller.js";

service("DiagramRestService", DiagramRestService);
directive("graph", GraphDirective);
template("reports-modal.tpl.html", reports_template);

export default ReportsModalController;
