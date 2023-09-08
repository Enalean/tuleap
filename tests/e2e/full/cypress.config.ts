import { cypress_config } from "@tuleap/cypress-configurator";
// eslint-disable-next-line import/no-extraneous-dependencies
import { defineConfig } from "cypress";

const e2e = { ...cypress_config.e2e };
export default defineConfig({ ...cypress_config, e2e });
