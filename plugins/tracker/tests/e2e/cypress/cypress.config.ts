import { cypress_config } from "@tuleap/cypress-configurator";
// eslint-disable-next-line import/no-extraneous-dependencies
import { defineConfig } from "cypress";

const e2e = { ...cypress_config.e2e };

// redirectionLimit is set to 25 because in tracker_artifact.cy.ts::must be able to create tracker from empty and configure it
// we add 22 fields and so the page is refreshed 22 times. As default limit is 20 the test fail
export default defineConfig({ ...cypress_config, e2e, redirectionLimit: 25 });
