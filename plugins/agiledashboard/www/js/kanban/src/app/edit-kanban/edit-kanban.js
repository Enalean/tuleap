import { directive, template } from "angular-tlp/angular-async.js";
import edit_template from "./edit-kanban.tpl.html";

import EditModalController from "./edit-kanban-controller.js";
import AutoFocusInputDirective from "./edit-kanban-autofocus-directive.js";

template("edit-kanban.tpl.html", edit_template);
directive("autoFocusInput", AutoFocusInputDirective);

export default EditModalController;
