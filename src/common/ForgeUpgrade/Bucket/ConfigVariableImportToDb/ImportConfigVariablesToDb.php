<?php
/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb;

use Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException;

final readonly class ImportConfigVariablesToDb
{
    public function __construct(private \PDO $dbh, private string $config_file_path)
    {
    }

    /**
     * @param Variable[] $variables_name
     * @throws BucketUpgradeNotCompleteException
     */
    public function import(array $variables_name): void
    {
        $variables = $this->getVariables();
        if (count($variables) === 0) {
            return;
        }

        $this->dbh->beginTransaction();
        $insert_stmt = $this->dbh->prepare('INSERT IGNORE INTO forgeconfig(name, value) VALUE (?, ?)');

        foreach ($variables_name as $variable) {
            if (isset($variables[$variable->getNameInFile()])) {
                $insert_stmt->execute([$variable->getNameInDb(), $variable->getValueAsString($variables[$variable->getNameInFile()])]);
            }
        }

        if (rename($this->config_file_path, $this->config_file_path . '.tuleapsave_' . time())) {
            $this->dbh->commit();
        } else {
            $this->dbh->rollBack();
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(sprintf('Could not rename the %s configuration file.', $this->config_file_path));
        }
    }

    private function getVariables(): array
    {
        if (! file_exists($this->config_file_path)) {
            return [];
        }
        include $this->config_file_path;
        return get_defined_vars();
    }
}
