# Meilisearch for Tuleap development

This section covers how to set up a local Meilisearch bakend server next
to your Tuleap development platform in order to develop or debug.

## Start Meilisearch container

First, start the Meilisearch container.

``` bash
you@workstation $> docker-compose up -d meilisearch
```

Once started, you should be able to reach the server using the following url: [https://tuleap-web.tuleap-aio-dev.docker/meilisearch-remote](https://tuleap-web.tuleap-aio-dev.docker/meilisearch-remote).

**Note:** If you encounter a **502 Bad Gateway** error, try the following:
*   Check the container logs (`docker-compose logs meilisearch`) to see if a database migration/upgrade is in progress or required.
*   Wait a few moments for the service to initialize completely, then refresh the page.

## Configure the Meilisearch integration

1.  **Activate the Meilisearch Plugin:**
  In your Tuleap instance's Site Administration area, ensure the `Meilisearch` plugin (`fts_meilisearch`) is installed and activated.

2.  **Configure Settings:**
Navigate to the plugin's settings page and fill in the required fields as follows:
  *   **Server URL:** `https://tuleap-web.tuleap-aio-dev.docker/meilisearch-remote`
  *   **API key:** `MEILI_MASTER_KEY_DO_NOT_USE_IN_PRODUCTION` (or your custom key if you changed it).
  *   **Index name:** Keep the default value `fts_tuleap`.

3.  **Ensure Smokescreen is Running:**
    Tuleap uses Smokescreen, a security proxy, to protect outgoing requests like those to Meilisearch. Make sure this service is running.
    ```bash
    sudo systemctl start tuleap-smokescreen
    sudo systemctl status tuleap-smokescreen
    ```

At this point, Meilisearch should be configured as the search backend for your Tuleap instance.

---

### Indexing Data

To make content searchable, you must run the initial indexing process. This can be a long and resource-intensive operation.

1.  **Identify Items to Index:**
    This command scans the database and flags all content that needs to be indexed.
    ```bash
    sudo -u codendiadm /usr/share/tuleap/src/utils/tuleap full-text-search:identify-all-items-to-index
    ```

2.  **Run the Indexing:**
    This second command sends all the flagged items to the Meilisearch server to be processed and added to the search index.
    ```bash
    sudo -u codendiadm /usr/share/tuleap/src/utils/tuleap full-text-search:index-all-pending-items
    ```

---

### Dashboard Access**

The Meilisearch dashboard provides a web interface to view the status of your indexes, documents, and search settings.
*   **Find the Container IP Address:** To access the dashboard directly, you need the Meilisearch container's IP address. You can find this using the Tuleap development environment's make command:
    ```bash
    make show-ips
    ```
*   **Access the Dashboard:** Open your browser and navigate to `http://<MEILISEARCH_CONTAINER_IP>:7700`.
