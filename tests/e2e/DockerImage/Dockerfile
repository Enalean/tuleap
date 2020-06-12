FROM cypress/base:14.0.0 AS cypress_bin_downloader
ARG CYPRESS_VERSION
RUN wget https://download.cypress.io/desktop/${CYPRESS_VERSION}?platform=linux -O cypress_bin.zip && unzip cypress_bin.zip

FROM cypress/base:14.0.0
COPY --from=cypress_bin_downloader /Cypress/ /Cypress/
ENV CYPRESS_RUN_BINARY /Cypress/Cypress
