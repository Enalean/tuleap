<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PHPWikiMigratorDao extends DataAccessObject {
    public function importWikiIntoPlugin($project_id) {
        $project_id = $this->getDa()->escapeInt($project_id);

        $this->enableExceptionsOnError();
        $this->startTransaction();
        try {
            $this->processWikiPages($project_id);
            $this->processWikiAttachments($project_id);
            $this->processWikiGlobalPermission($project_id);
            $this->processWikiMiscellaneous($project_id);
        } catch (DataAccessException $e) {
            $this->rollBack();
            throw $e;
        }
        $this->commit();
    }

    private function processWikiPages($project_id) {
        $map_wiki_pages_id = array();
        $dar_wiki_pages    = $this->retrieve("SELECT * FROM wiki_page WHERE group_id = $project_id");
        if ($dar_wiki_pages) {
            foreach ($dar_wiki_pages as $row) {
                $old_id   = $this->getDa()->escapeInt($row['id']);
                $pagename = $this->getDa()->quoteSmart($row['pagename']);
                $hits     = $this->getDa()->escapeInt($row['hits']);
                $pagedata = $this->getDa()->quoteSmart($row['pagedata']);

                $new_id = $this->updateAndGetLastId("INSERT INTO plugin_phpwiki_page(pagename, hits, pagedata, group_id)
                                                         VALUES($pagename, $hits, $pagedata, $project_id)");
                $map_wiki_pages_id[$old_id] = $new_id;
            }
        }

        foreach ($map_wiki_pages_id as $old_id => $new_id) {
            $sql_wiki_version  = "INSERT INTO plugin_phpwiki_version(id, version, mtime, minor_edit, content, versiondata)
                                      SELECT $new_id, version, mtime, minor_edit, content, versiondata
                                      FROM wiki_version WHERE id = $old_id";
            $this->update($sql_wiki_version);
            $sql_wiki_recent   = "INSERT INTO plugin_phpwiki_recent(id, latestversion, latestmajor, latestminor)
                                      SELECT $new_id, latestversion, latestmajor, latestminor
                                      FROM wiki_recent WHERE id = $old_id";
            $this->update($sql_wiki_recent);
            $sql_wiki_nonempty = "INSERT INTO plugin_phpwiki_nonempty(id)
                                      SELECT $new_id
                                      FROM wiki_nonempty WHERE id = $old_id";
            $this->update($sql_wiki_nonempty);
        }

        $dar_wiki_links = $this->retrieve("SELECT linkfrom, linkto FROM wiki_link JOIN wiki_page ON (linkfrom = id OR linkto = id) WHERE group_id = $project_id");
        if ($dar_wiki_links) {
            foreach ($dar_wiki_links as $row) {
                $link_from_new = $map_wiki_pages_id[$row['linkfrom']];
                $link_to_new   = $map_wiki_pages_id[$row['linkto']];

                $this->update("INSERT INTO plugin_phpwiki_link(linkfrom, linkto) VALUES($link_from_new, $link_to_new)");
            }
        }

        $this->processWikiPagesPermissions($project_id, $map_wiki_pages_id);
    }

    private function processWikiPagesPermissions($project_id, array $map_wiki_pages_id) {
        $dar_wiki_pages_permissions = $this->retrieve("SELECT object_id, ugroup_id
                                                           FROM permissions
                                                           JOIN wiki_page ON object_id = id
                                                           WHERE permission_type = 'WIKIPAGE_READ' AND group_id = $project_id");

        if ($dar_wiki_pages_permissions) {
            foreach ($dar_wiki_pages_permissions as $row) {
                $object_id       = $map_wiki_pages_id[$row['object_id']];
                $ugroup_id       = $this->getDa()->escapeInt($row['ugroup_id']);
                $this->update("INSERT INTO permissions(permission_type, object_id, ugroup_id)
                                   VALUES('PHPWIKIPAGE_READ', '$object_id', $ugroup_id)");
            }
        }
    }

    private function processWikiAttachments($project_id) {
        $map_wiki_attachments_id = array();
        $dar_wiki_attachments    = $this->retrieve("SELECT * FROM wiki_attachment WHERE group_id = $project_id");
        if ($dar_wiki_attachments) {
            foreach ($dar_wiki_attachments as $row) {
                $old_id          = $this->getDa()->escapeInt($row['id']);
                $name            = $this->getDa()->quoteSmart($row['name']);
                $filesystem_name = $this->getDa()->quoteSmart($row['filesystem_name']);
                $delete_date     = $row['delete_date'] === 'NULL' ? $this->getDa()->escapeInt($row['delete_date']) : 'NULL';

                $new_id = $this->updateAndGetLastId("INSERT INTO plugin_phpwiki_attachment(group_id, name, filesystem_name, delete_date)
                                                         VALUES($project_id, $name, $filesystem_name, $delete_date)");
                $map_wiki_attachments_id[$old_id] = $new_id;
            }
        }

        $map_wiki_attachments_rev_id = array();
        $dar_wiki_attachments_rev   = $this->retrieve("SELECT wiki_attachment_revision.id, attachment_id, user_id, date, revision, mimetype, size
                                                            FROM wiki_attachment_revision JOIN wiki_attachment ON attachment_id = wiki_attachment.id
                                                            WHERE group_id = $project_id");
        if ($dar_wiki_attachments_rev) {
            foreach ($dar_wiki_attachments_rev as $row) {
                $old_id        = $this->getDa()->escapeInt($row['id']);
                $attachment_id = $this->getDa()->escapeInt($row['attachment_id']);
                $user_id       = $this->getDa()->escapeInt($row['user_id']);
                $date          = $this->getDa()->escapeInt($row['date']);
                $revision      = $this->getDa()->escapeInt($row['revision']);
                $mimetype      = $this->getDa()->quoteSmart($row['mimetype']);
                $size          = $this->getDa()->escapeInt($row['size']);

                $new_id = $this->updateAndGetLastId("INSERT INTO plugin_phpwiki_attachment_revision(attachment_id, user_id, date, revision, mimetype, size)
                                                         VALUES($map_wiki_attachments_id[$attachment_id], $user_id, $date, $revision, $mimetype, $size)");
                $map_wiki_attachments_rev_id[$old_id] = $new_id;
            }
        }

        $dar_wiki_attachments_log = $this->retrieve("SELECT * FROM wiki_attachment_log WHERE group_id = $project_id");
        if ($dar_wiki_attachments_log) {
            foreach ($dar_wiki_attachments_log as $row) {
                $user_id                     = $this->getDa()->escapeInt($row['user_id']);
                $wiki_attachment_id          = $this->getDa()->escapeInt($row['wiki_attachment_id']);
                $wiki_attachment_revision_id = $this->getDa()->escapeInt($row['wiki_attachment_revision_id']);
                $time                        = $this->getDa()->escapeInt($row['time']);

                $this->update("INSERT INTO plugin_phpwiki_attachment_log
                                   VALUES($user_id, $project_id, $map_wiki_attachments_id[$wiki_attachment_id],
                                   $map_wiki_attachments_rev_id[$wiki_attachment_revision_id], $time)");
            }
        }

        $this->update("INSERT INTO plugin_phpwiki_attachment_deleted(group_id, name, filesystem_name, delete_date, purge_date)
                           SELECT group_id, name, filesystem_name, delete_date, purge_date
                           FROM wiki_attachment_deleted
                           WHERE group_id = $project_id");

        $this->processWikiAttachmentsPermissions($project_id, $map_wiki_attachments_id);
    }

    private function processWikiAttachmentsPermissions($project_id, array $map_wiki_attachments_id) {
        $dar_wiki_attachments_permissions = $this->retrieve("SELECT object_id, ugroup_id
                                                                 FROM permissions
                                                                 JOIN wiki_attachment ON object_id = id
                                                                 WHERE permission_type = 'WIKIATTACHMENT_READ' AND group_id = $project_id");
        if ($dar_wiki_attachments_permissions) {
            foreach ($dar_wiki_attachments_permissions as $row) {
                $object_id       = $map_wiki_attachments_id[$row['object_id']];
                $ugroup_id       = $this->getDa()->escapeInt($row['ugroup_id']);
                $this->update("INSERT INTO permissions(permission_type, object_id, ugroup_id)
                                   VALUES('PHPWIKIATTACHMENT_READ', '$object_id', $ugroup_id)");
            }
        }
    }

    private function processWikiGlobalPermission($project_id) {
        $this->update("INSERT INTO permissions
                           SELECT 'PHPWIKI_READ', object_id, ugroup_id
                           FROM permissions
                           WHERE permission_type= 'WIKI_READ' AND object_id = $project_id");
    }

    private function processWikiMiscellaneous($project_id) {
        $this->update("INSERT INTO plugin_phpwiki_group_list(group_id, wiki_name, wiki_link, description, rank, language_id)
                           SELECT group_id, wiki_name, wiki_link, description, rank, language_id
                           FROM wiki_group_list
                           WHERE group_id = $project_id");
        $this->update("INSERT INTO plugin_phpwiki_log SELECT * FROM wiki_log WHERE group_id = $project_id");
        $this->update("UPDATE service SET is_used = 1 WHERE short_name = 'plugin_phpwiki' AND group_id = $project_id");
        $this->update("DELETE FROM service WHERE short_name = 'wiki' AND group_id = $project_id");
    }
}