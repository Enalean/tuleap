import "babel-polyfill";
import "tlp-mocks";

import "./index.js"; // To have actual coverage
import "./api/rest-querier.spec.js";
import "./store/actions.spec.js";
import "./store/getters.spec.js";
import "./store/mutations.spec.js";
