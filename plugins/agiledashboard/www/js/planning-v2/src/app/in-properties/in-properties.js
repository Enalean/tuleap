import angular from "angular";
import "angular-moment";

import InPropertiesFilter from "./in-properties-filter.js";

export default angular
    .module("inproperties.filter", ["angularMoment"])
    .filter("InPropertiesFilter", InPropertiesFilter).name;
