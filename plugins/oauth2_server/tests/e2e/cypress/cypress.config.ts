import { cypress_config } from "@tuleap/cypress-configurator";
// eslint-disable-next-line import/no-extraneous-dependencies
import { defineConfig } from "cypress";

export default defineConfig({
    ...cypress_config,
    chromeWebSecurity: false, // Disable to be able to follow Location headers sent by rp-oidc
});
