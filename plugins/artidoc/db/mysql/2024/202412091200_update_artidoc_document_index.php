<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202412091200_update_artidoc_document_index extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Update plugin_artidoc_document table index';
    }

    public function up(): void
    {
        if ($this->api->indexNameExists('plugin_artidoc_document', 'idx_uniq_artifact')) {
            $this->api->dbh->exec(
                <<<EOS
                ALTER TABLE plugin_artidoc_document DROP INDEX idx_uniq_artifact;
                EOS
            );
        }
        $this->api->addIndex(
            'plugin_artidoc_document',
            'idx_item_artifact',
            <<<EOS
            ALTER TABLE plugin_artidoc_document ADD INDEX idx_item_artifact (item_id, artifact_id);
            EOS
        );
        $this->api->addIndex(
            'plugin_artidoc_document',
            'idx_artifact',
            <<<EOS
            ALTER TABLE plugin_artidoc_document ADD INDEX idx_artifact (artifact_id);
            EOS
        );
    }
}
