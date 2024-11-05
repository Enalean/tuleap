# Database

## Secure DB against SQL injections

All code related to database MUST rely on prepared statements to pass
parameters to a SQL query.

Example of DataAccessObject:

``` php
declare(strict_types=1);

namespace Tuleap\Git;

use Tuleap\DB\DataAccessObject;
use ParagonIE\EasyDB\EasyStatement;

class RepositoryDao extends DataAccessObject
{
    public function searchByName(int $project_id, string $name) : array
    {
        $sql = 'SELECT *
                FROM plugin_git_repositories
                WHERE project_id = ? AND name = ?';

        return $this->getDB()->run($sql, $project_id, $name);
    }

    public function searchByProjectIDs(array $project_ids) : array
    {
        $project_ids_in_condition = EasyStatement::open()->in('?*', $project_ids);

        $sql = 'SELECT *
                FROM plugin_git_repositories
                WHERE project_id IN ($project_ids_in_condition)';

        return $this->getDB()->safeQuery($sql, $project_ids_in_condition->values());
    }
}
```

You might find existing code using the `\DataAccessObject` class or
`db_*()` functions, in that case you will need to use the dedicated
escaping methods (`\DataAccessObject::quoteSmart`,
`\DataAccessObject::escapeInt`, `db_es` and `db_ei`). The usage of these
deprecated interfaces should be avoided.

## Usage of auto incremented ids

New code should not rely on `AUTO_INCREMENT` feature of MySQL. UUIDs must be used instead.

Example of table creation:
```sql
CREATE TABLE IF NOT EXISTS plugin_foobar (
    id BINARY(16) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
```

Then, when a new entry is added to the table:
```php
    public function addFoo(string $name): void
    {
        $id = $this->uuid_factory->buildUUIDBytes();
        $this->getDB()->insert(
            'plugin_foobar',
            ['id' => $id, 'name' => $name]
        );
    }
```

For more context about this policy, you can check [ADR 0028 Prevent data loss](../decisions/0028-prevent-data-loss.md).

## Database structure change with ForgeUpgrade

Each version of Tuleap is likely to differ from the next one on many
levels including in it\'s database structure. To manage this,
ForgeUpgrade? has inbuilt internal functionality akin to that of
commonly used tools such as dbdeploy or MIGRATEdb. Whereas the latter
use sql and xml scripts to describe each database change, ForgeUpgrade?
uses php scripts.

The upgrading of the database happens when the above command is run:

:   ``` bash
    $> tuleap-cfg site-deploy:forgeupgrade
    ```

In a dev environment an helper is available to run the DB migrations:

:   ``` bash
    $> make dev-forgeupgrade
    ```

### Database scripts

-   The scripts are located within the db/mysql/updates/yyyy/ directory
    of each plugin and of the Tuleap core, e.g.
    `/path/to/tuleap/cardwall/db/mysql/updates/2012/`
-   Each script is php file that begins with the Enalean license and
    contains a single class.
-   The class name is structured as follows:
    byyyyMMddhhmm_description_of_change_being_made and MUST extend the
    class `ForgeUpgrade_Bucket`.

Where:

-   `yyyy` is the year;
-   `MM` the month;
-   `dd` the day and so on.

The \"b\" is not symbolic of anything and must always be the first
letter in the class name.

### Sample script

``` php
<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * ....
 */

declare(strict_types=1);

class b201806051455_add_cardwall_on_top_table extends ForgeUpgrade_Bucket // @phpcs:ignore
{
    public function description()
    {
        return <<<EOT
        Add table to store trackers that enable cardwall on top of them
        EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top(
                  tracker_id int(11) NOT NULL PRIMARY KEY
                )";
        $this->db->createTable('plugin_cardwall_on_top', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_cardwall_on_top')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_on_top table is missing');
        }
    }
}
```

When creating a new script, the only methods you generally need to
change are description() and up().
