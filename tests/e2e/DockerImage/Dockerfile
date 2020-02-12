FROM cypress/base:12.12.0
ENV CYPRESS_CACHE_FOLDER /var/cache/cypress/
RUN npm install --unsafe-perm=true -g cypress@^4.0.0 && cypress verify
