FROM cypress/base:10
ENV CYPRESS_CACHE_FOLDER /var/cache/cypress/
RUN npm install --unsafe-perm=true -g cypress@^3.0.3 && cypress verify