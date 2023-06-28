import angular from "angular";
import "../themes/main.scss";

import HighlightFilter from "./highlight-filter.js";
import CardFieldsDirective from "./card-fields-directive.js";
import CardFieldsService from "./card-fields-service.js";
import tuleapSimpleFieldDirective from "./tuleap-simple-field-directive.js";
import tuleapCardLinkDirective from "./tuleap-card-link-directive.js";
import cardComputedFieldDirective from "./card-computed-field/card-computed-field-directive.js";
import cardTextFieldDirective from "./card-text-field/card-text-field-directive.js";

export default angular
    .module("card-fields", [])
    .service("CardFieldsService", CardFieldsService)
    .directive("cardFields", CardFieldsDirective)
    .directive("tuleapSimpleField", tuleapSimpleFieldDirective)
    .directive("tuleapCardLink", tuleapCardLinkDirective)
    .directive("cardComputedField", cardComputedFieldDirective)
    .directive("cardTextField", cardTextFieldDirective)
    .filter("tuleapHighlight", HighlightFilter).name;
