ARG DOCKER_REGISTRY
FROM ${DOCKER_REGISTRY}/cypress/base@sha256:48610d161ad6dc28a6d92fbfe50d86b07c55b66290de6d910a85c049a249c031 AS cypress_bin_downloader
ARG CYPRESS_VERSION
RUN apt-get update \
    && apt-get install --yes unzip \
    && apt-get upgrade --yes ca-certificates \
    && wget https://download.cypress.io/desktop/${CYPRESS_VERSION}?platform=linux -O cypress_bin.zip \
    && unzip cypress_bin.zip

FROM ${DOCKER_REGISTRY}/cypress/base@sha256:48610d161ad6dc28a6d92fbfe50d86b07c55b66290de6d910a85c049a249c031
RUN apt-get update \
    && apt-get install --yes curl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=cypress_bin_downloader /Cypress/ /Cypress/
ENV CYPRESS_RUN_BINARY /Cypress/Cypress
RUN npm install -g junit-report-merger
