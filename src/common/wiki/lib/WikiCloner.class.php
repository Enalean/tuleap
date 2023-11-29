<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright 2007, STMicroelectronics
 *
 * Originally written by Sabri LABBENE <sabri.labbene@st.com>
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

/**
 * Creates a clone of a Wiki.
 *
 * This class is a part of the Model of Wiki Service it aims to be the
 * interface between data corresponding to a Wiki Service (instance of
 * PhpWiki for Codendi) and Codendi application
 *
 */
class WikiCloner
{
    public mixed $tmpl_wiki_exist;


 /**
   * Constructor
   *
   *  @access public
   *  @param Template project identifier
   *  @param New project identifier
   *
   */
    public function __construct($template_id = 0, $group_id = 0)
    {
        $this->template_id      = (int) $template_id;
        $this->group_id         = (int) $group_id;
        $this->tmpl_wiki_exist  = null;
        $this->new_wiki_is_used = null;
    }

 /**
   *
   *  Check if new project's wiki service is used.
   *
   *  @return bool .
   *
   *
   */

    public function newWikiIsUsed()
    {
        if ($this->new_wiki_is_used === null) {
            $res                    = db_query(sprintf("SELECT is_used FROM service WHERE group_id=%d AND short_name='%s'", $this->group_id, $this->escapeString("wiki")));
            $this->new_wiki_is_used = (db_result($res, 0, 'is_used') ==  1);
        }
        return $this->new_wiki_is_used;
    }

 /**
   *
   *  Check if template project has wiki service enabled
   *  @return bool
   *
   */
    public function templateWikiExists()
    {
        if ($this->new_wiki_is_used === null) {
            $res                   = db_query('SELECT count(*) AS nb FROM wiki_page'
                         . ' WHERE group_id=' . db_ei($this->template_id));
            $this->tmpl_wiki_exist = (db_result($res, 0, 'nb') > 0);
        }
        return $this->tmpl_wiki_exist;
    }

    public function templateWikiHaveAttachments()
    {
        $res                    = db_query('SELECT count(*) AS nb FROM wiki_attachment'
                    . ' WHERE group_id=' . db_ei($this->template_id));
        $tmpl_wiki_attach_exist = (db_result($res, 0, 'nb') > 0);
        return $tmpl_wiki_attach_exist;
    }

 /**
   *
   *  Create a clone of the template wiki. It includes:
   *  - the clone wiki pages (wiki_page table).
   *
   *  @return bool .
 * push
   */
    public function CloneWiki()
    {
        $this->addWikiEntries();
        $arr = $this->cloneWikiPageTable();
        $this->cloneWikiVersionTable($arr);
        $this->addNonEmptyInfo($arr);
        $this->addWikiLinkEntries($arr);
        $this->cloneWikiRecentTable($arr);
        if ($this->templateWikiHaveAttachments()) {
            $attachments_array   = $this->cloneWikiAttachementTable();
            $attachments_rev_arr = $this->cloneWikiAttachmentRevisionTable($attachments_array);
            $this->cloneWikiAttachmentLogTable($attachments_array, $attachments_rev_arr);
        }
        $this->cloneWikiPermission();
        $this->cloneWikiPagesPermissions($arr);
    }

 /**
   *
   *  Creates wiki entries for the new wiki in wiki_group_list
   *  table. It is in the same language as the template wiki.
   *
   *
   */
    public function addWikiEntries()
    {
        $language = $this->getTemplateLanguageId();
        $we_data  = $this->getTemplateWikiEntries();
        foreach ($we_data as $id => $data) {
            $result = db_query(sprintf(
                "INSERT INTO wiki_group_list (group_id, wiki_name, wiki_link, description, rank, language_id)"
                                    . "VALUES(%d, '%s', '%s', '%s', %d, %s)",
                $this->group_id,
                $this->escapeString($data['wiki_name']),
                $this->escapeString($data['wiki_link']),
                $this->escapeString($data['description']),
                (int) $data['rank'],
                $this->escapeString($language)
            ));
        }
    }

 /**
   *
   *  Clone Template Wiki version data.
   *
   *  @param array of initial (template) and cloned wiki pages ids.
   *  Something like: template page id => new page id.
   *
   */
    public function cloneWikiVersionTable($array)
    {
        foreach ($array as $key => $value) {
            $tmpl_version_data = $this->getTemplateWikiVersionData($key);
            $result            = db_query(sprintf("select version, mtime, minor_edit, content FROM wiki_version WHERE id=%d", $key));
            while ($row = db_fetch_array($result)) {
                 $num_ver = $row['version'];
                 $res     = db_query(sprintf(
                     "INSERT INTO wiki_version (id, version, mtime, minor_edit, content, versiondata)"
                     . "VALUES(%d, %d, %d, %d, '%s', '%s')",
                     db_ei($value),
                     db_ei($num_ver),
                     db_ei($row['mtime']),
                     db_ei($row['minor_edit']),
                     $this->escapeString($row['content']),
                     $this->escapeString($this->_serialize($tmpl_version_data[$num_ver]))
                 ));
            }
        }
    }

 /**
   *
   *  Clone template project's 'wiki_page' table.
   *
   *
   *  @return hash of template wiki pages ids as keys and new wiki
   *  pages ids as their values.
   */
    public function cloneWikiPageTable()
    {
        $ids    = [];
        $result = db_query(sprintf("SELECT id, pagename FROM wiki_page WHERE group_id=%d", $this->template_id));
        while ($row = db_fetch_array($result)) {
            $pagename           =  $row['pagename'];
            $tmpl_page_id       = $row['id'];
            $page_data          = $this->getTemplatePageData($pagename);
            $new_data           = $this->createNewPageData($page_data);
            $id                 = $this->insertNewWikiPage($new_data, $pagename);
            $ids[$tmpl_page_id] = $id;
        }
        return $ids;
    }

 /**
  *  Clone wiki_recent  table
  *
  *  @param array: tmplpage_id => newpage_id
  *
  */
    public function cloneWikiRecentTable($array)
    {
        $recent_infos = [];
        foreach ($array as $tmpl_id => $new_id) {
            $recent_infos = $this->getTemplatePageRecentInfos($tmpl_id);
            if (! empty($recent_infos)) {
                if (! empty($recent_infos['latestminor'])) {
                    $result = db_query(sprintf(
                        "INSERT INTO wiki_recent (id, latestversion, latestmajor, latestminor)"
                              . "VALUES(%d, %d, %d, %d)",
                        $new_id,
                        $recent_infos['latestversion'],
                        $recent_infos['latestmajor'],
                        $recent_infos['latestminor']
                    ));
                } else {
                    $result = db_query(sprintf("INSERT INTO wiki_recent (id, latestversion, latestmajor)"
                     . "VALUES(%d, %d, %d)", $new_id, $recent_infos['latestversion'], $recent_infos['latestmajor']));
                }
            }
        }
    }

 /**
   *  Fills into wiki_link table the sources and targets ids of pages created
   *  by the clone
   *
   *  @param hash: template pages ids => new pages ids
   *
   *
   */
    public function addWikiLinkEntries($array)
    {
        foreach ($array as $tmpl_id => $new_id) {
            $result = db_query(sprintf("select linkto FROM wiki_link WHERE linkfrom=%d", $tmpl_id));
            while ($row = db_fetch_array($result)) {
               // Find the new page target link
                $clone_id = $this->getWikiPageCloneId($array, $row['linkto']);
        // Insert a new link row in wiki_link table
                $res = db_query(sprintf("INSERT INTO wiki_link (linkfrom, linkto) VALUES (%d, %d)", $new_id, $clone_id));
            }
        }
    }

 /**
   *  Clone wiki_attachment table and create attachment directories. It
   *  also clones the permissions set on the template attachments.
   *
   *  @return hash : template attachment id => new attachment id
   *
   */
    public function cloneWikiAttachementTable()
    {
        //Create attachement directory
        if (is_dir(ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->template_id)) { // Otherwise, directory is created with perms '000'
            mkdir(ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->group_id, fileperms(ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->template_id));
        }

        $ids    = [];
        $result = db_query(sprintf("SELECT id, name FROM wiki_attachment WHERE group_id=%d", db_ei($this->template_id)));
        while ($row = db_fetch_array($result)) {
            $id       = $row['id'];
            $name     = $row['name'];
            $ids[$id] = $this->insertNewAttachment($name);
     // Create a directory for attachment file revisions.
            $dir_mode = $this->getFileMode(ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->template_id . '/' . $name);
            $this->createAttachmentDir($name, $dir_mode);
            if ($this->attachmentHasAPermission($id)) {
                     $permission = $this->getAttachmentPermission($id);
                     $this->insertNewAttachmentPermission($ids[$id], $permission);
            }
        }
        return $ids;
    }

 /**
   *
   *  Checks if an attachment has a specific permission setted.
   *
   *  @param int : template attachment id.
   *
   *  @return bool .
   *
   */
    public function attachmentHasAPermission($attachment_id)
    {
        $attachment_id = db_ei($attachment_id);
        $result        = db_query(
            "SELECT count(*) AS nb FROM permissions WHERE permission_type='WIKIATTACHMENT_READ' AND object_id=CAST($attachment_id AS CHAR CHARACTER SET utf8)"
        );
        if (db_result($result, 0, 'nb') > 0) {
            return true;
        } else {
            return false;
        }
    }

 /**
  *
  * Returns mapped ugroup_id in the new project
  *
  *  @param int : ugroup_id at original project.
  *
  *  @returns int : mapped ugroup_id.
  */
    public function getMappedUGroupId($ugid)
    {
        if ($ugid > 100) {
            $res = db_query(sprintf("SELECT dst_ugroup_id FROM ugroup_mapping WHERE to_group_id=%d AND src_ugroup_id=%d", db_ei($this->group_id), db_ei($ugid)));
            return db_result($res, 0, 'dst_ugroup_id');
        } else {
            return $ugid;
        }
    }

  /**
  *
  *  Fetches the permission data set on a template attachment.
  *
  *  @param int : template attachment id.
  *
  *  @return int : authorized ugroup_id.
  *
  */
    public function getAttachmentPermission($attachment_id)
    {
        $attachment_id = db_ei($attachment_id);
        $result        = db_query(
            "SELECT ugroup_id FROM permissions WHERE permission_type='WIKIATTACHMENT_READ' AND object_id=CAST($attachment_id AS CHAR CHARACTER SET utf8)"
        );
        $ugroup        = db_result($result, 0, 'ugroup_id');
        return $this->getMappedUGroupId($ugroup);
    }

 /**
   *
   *  Clone the permission set on the template attachment.
   *
   *  @param int : template attachment id
   *
   *  @param int : cloned attachment id.
   *
   */
    public function insertNewAttachmentPermission($new_attachment_id, $permission)
    {
        $new_attachment_id = db_ei($new_attachment_id);
        $permission        = db_ei($permission);
        db_query("INSERT INTO permissions (permission_type, object_id, ugroup_id)
               VALUES ('WIKIATTACHMENT_READ', CAST($new_attachment_id AS CHAR CHARACTER SET utf8), $permission)");
    }

 /**
   *
   *  Create attachment directory in new project attachment space..
   *
   *  @param string : name of the attachment
   *
   */
    public function createAttachmentDir($name, $mode)
    {
        mkdir(ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->group_id . '/' . $name, $mode);
    }

 /**
   *  Clone wiki_attachment_revision table and attachment file revisions.
   *
   *  @param hash: template attachment revision id => new attachment revision id.
   *
   *
   */
    public function cloneWikiAttachmentRevisionTable($array)
    {
        $array_rev = [];
        foreach ($array as $tmpl_id => $new_id) {
            $result = db_query(sprintf("SELECT user_id, date, revision, mimetype, size FROM wiki_attachment_revision WHERE attachment_id=%d", db_ei($tmpl_id)));
            while ($row = db_fetch_array($result)) {
                $res = db_query(sprintf(
                    "INSERT INTO wiki_attachment_revision (attachment_id, user_id, date, revision, mimetype, size)"
                                 . "VALUES (%d, %d, %d, %d, '%s', %d)",
                    db_ei($new_id),
                    db_ei($row['user_id']),
                    db_ei($row['date']),
                    db_ei($row['revision']),
                    $this->escapeString($row['mimetype']),
                    db_ei($row['size'])
                ));
                if (db_affected_rows($res) > 0) {
                    $sql = db_query(sprintf("SELECT id from wiki_attachment_revision WHERE attachment_id=%d AND revision=%d", db_ei($new_id), db_ei($row['revision'])));
                    if (db_numrows($sql) > 0) {
                        $array_rev[$tmpl_id] = db_result($sql, 0, 'id');
                        // Clone attachment file revision.
                        $this->cloneAttachmentFileRevision($new_id, $row['revision']);
                    }
                }
            }
        }
        return $array_rev;
    }

 /**
   *
   *  Clone an attachment file revision.
   *
   *  @param int : attachment id.
   *
   *  @param string : attachment name.
   *
   *
   */
    public function cloneAttachmentFileRevision($id, $revision_num)
    {
         $result = db_query(sprintf("SELECT name from wiki_attachment where id=%d", $id));
        if (db_numrows($result) > 0) {
            $attacment_name = db_result($result, 0, 'name');
            $src            = ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->template_id . '/' . $attacment_name . '/' . $revision_num;
            $dst            = ForgeConfig::get('sys_wiki_attachment_data_dir') . '/' . $this->group_id . '/' . $attacment_name . '/' . $revision_num;
            copy($src, $dst);
            $file_mode = $this->getFileMode($src);
            chmod($dst, $file_mode);
        }
    }

  /**
  *
  *  Gets a file mode.
  *
  *  @param string : path to the file or directory.
  *
  *  @returns string : octal mode of the file.
  *
  */
    public function getFileMode($file)
    {
        return fileperms($file);
    }

 /**
   *  Clone wiki_attachment_log table.
   *
   *  @param hash: template attachment id => new attachment id.
   *
   */
    public function cloneWikiAttachmentLogTable($array, $array_rev)
    {
        foreach ($array as $tmpl_id => $new_id) {
            $result = db_query(sprintf("SELECT * FROM wiki_attachment_log WHERE group_id=%d AND wiki_attachment_id=%d", $this->template_id, $tmpl_id));
            while ($row = db_fetch_array($result)) {
                $res = db_query(sprintf("INSERT INTO wiki_attachment_log (user_id, group_id, wiki_attachment_id, wiki_attachment_revision_id, time)"
                . "VALUES (%d, %d, %d, %d, %d)", db_ei($row['user_id']), db_ei($this->group_id), db_ei($new_id), db_ei($array_rev[$tmpl_id]), db_ei($row['time'])));
            }
        }
    }

 /**
   *  fetches recent infos of template wiki page
   *
   *  @param int: id of the template wiki page
   *
   *  @return array: contains latestversion, latestmajor and
   *  latestminor revision numbers.
   *
   */
    public function getTemplatePageRecentInfos($id)
    {
        $recent = [];
        $result = db_query(sprintf("SELECT latestversion, latestmajor, latestminor FROM wiki_recent where id=%d", $id));
        if (db_numrows($result) > 0) {
            $recent = ['latestversion' => (int) db_result($result, 0, 'latestversion'), 'latestmajor' => (int) db_result($result, 0, 'latestmajor')
                         , 'latestminor' => (int) db_result($result, 0, 'latestminor'),
            ];
        } else {
            return [];
        }
        return $recent;
    }

 /**
   *
   *  fetches template wiki_name, description, language_id, etc.
   *
   *  @return array:
   *
   *
   */
    public function getTemplateWikiEntries()
    {
        $we     = [];
        $result = db_query(sprintf("SELECT id, wiki_name, wiki_link, description, rank, language_id FROM wiki_group_list WHERE group_id=%d", $this->template_id));
        while ($row = db_fetch_array($result)) {
            $id      = $row['id'];
            $we[$id] = ['wiki_name' => $row['wiki_name'], 'wiki_link' => $row['wiki_link'], 'description' => $row['description'], 'rank' => $row['rank']];
        }
        return $we;
    }

 /**
   *
   *  Look for the template wiki language id.
   *
   *  @return string : template wiki language id.
   *
   *
   */
    public function getTemplateLanguageId()
    {
        $result = db_query(sprintf("SELECT language_id FROM wiki_group_list WHERE group_id=%d", $this->template_id));
        if ($row = db_fetch_array($result)) {
            $lang = $row['language_id'];
            return $lang;
        } else {
            return 0;
        }
    }

 /**
   *
   *  Get pagedata information from wiki_page table.
   *
   *  @params pagename : name of any wiki page stored in the db.
   *  @return deserialized page data hash.
   */
    public function getTemplatePageData($pagename)
    {
        $result = db_query(sprintf("SELECT pagedata FROM wiki_page WHERE pagename='%s' AND group_id=%d", db_es($pagename), $this->template_id));
        while ($page_data = db_fetch_array($result)) {
            return $this->_deserialize($page_data['pagedata']);
        }
    }

 /**
   *
   *  Get versiondata information of a template wiki page from wiki_version table.
   *
   *  @params id : id of the template wiki page stored in the db.
   *  @return hash like:
   *  $key : version number
   *  $value : deserialised version data hash
   */
    public function getTemplateWikiVersionData($id)
    {
        $version = [];
        $result  = db_query(sprintf("SELECT version, versiondata FROM wiki_version WHERE id=%d", $id));
        while ($row = db_fetch_array($result)) {
            $version_number           = $row['version'];
            $version_data             = $this->_deserialize($row['versiondata']);
            $version[$version_number] = $version_data;
        }
        return $version;
    }

 /**
   *  returns the id of a wiki page clone
   *
   *  @param hash : template page id => new page id.
   *  @param int : template page id.
   *
   *  @return int: id of a wiki page clone
   */
    public function getWikiPageCloneId($array, $tmpl_page_id)
    {
        foreach ($array as $tmpl_id => $new_id) {
            if ($tmpl_id == $tmpl_page_id) {
                 return $new_id;
            }
        }
    }

 /**
   *  Create a new pagedata array.
   *  Monitoring data is not copied.
   *  Other data (lockinfo and user prefs) are copied.
   *
   *  @params data : array of page data
   *  @return data : array of the new page data
   */
    public function createNewPageData($data)
    {
        if (empty($data)) {
            return [];
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                 // Do not copy monitoring data of 'global_data' wiki page.
                        if ($k == 'notify') {
                            unset($data[$key][$k]);
                        }
                    }
                } else { // $value is serialized. Actually it is only  in user pages case.
                         $arr = $this->_deserialize($value);
                    if (is_array($arr)) {
                        foreach ($arr as $i => $j) {
                            // Do not copy monitoring data of user pages.
                            if ($i == 'notifyPages') {
                                unset($arr[$i]);
                            }
                        }
                    }
                         $data[$key] = $this->_serialize($arr);
                }
         // Keep 'date' and 'locked' infos as in the template page.
                if ($key == 'date' or $key == 'locked') {
                    $data[$key] = $value;
                }
            }
            return $data;
        }
    }

  /**
   *
   *  Check if a template page is non empty
   *
   *  @param int : id of template wiki page
   *  @return bool : true if the template page is considered as non empty in
 * the template project.
   *
   */
    public function isTemplatePageNonEmpty($id)
    {
        $result = db_query(sprintf("SELECT * from wiki_nonempty where id=%d", $id));
        if (db_numrows($result)) {
            return true;
        } else {
            return false;
        }
    }

  /**
   *
   *  Adds an entry in 'wiki_nonempty' table for pages considered as non ampty
   *  in the template project
   *
   *  @param array : new pages ids and template pages ids.
   *
   */
    public function addNonEmptyInfo($array)
    {
        foreach ($array as $tmpl => $new) {
            if ($this->isTemplatePageNonEmpty($tmpl)) {
                $result = db_query(sprintf("INSERT INTO wiki_nonempty (id) VALUES(%d)", $new));
            }
        }
    }

 /**
   *  Creates a clone of a template attachment
   *
   *  @param string: name of the attachement
   *
   *  @return int: id of the new attachment
   */
    public function insertNewAttachment($name)
    {
        $result = db_query(sprintf("INSERT INTO wiki_attachment (group_id, name)"
        . "VALUES(%d, '%s')", $this->group_id, $this->escapeString($name)));
        if (! empty($result)) {
            $res = db_query(sprintf("SELECT id FROM wiki_attachment WHERE group_id=%d AND name='%s'", $this->group_id, $this->escapeString($name)));
            while ($row = db_fetch_array($res)) {
                $id = $row[0];
            }
            return $id;
        }
    }

 /**
   *  Create a clone of a wiki page by inserting a new row in wiki_page table.
   *
   *  @params array data : array of page data
   *  @params string pagename : escaped wiki page name
   *  @return int id : id of the created page
   *
   */
    public function insertNewWikiPage($data, $pagename)
    {
        $result = db_query(sprintf(
            "INSERT INTO wiki_page (pagename, hits, pagedata, group_id)"
            . "VALUES('%s', %d,  '%s', %d)",
            db_es($pagename),
            0,
            db_es($this->_serialize($data)),
            $this->group_id
        ));
        if (! empty($result)) {
            $res = db_query(sprintf("SELECT id from wiki_page where pagename='%s' and group_id=%d", db_es($pagename), $this->group_id));
            while ($row = db_fetch_array($res)) {
                $id = $row[0];
            }
            return $id;
        }
    }

 /**
   *
   *  Sets the same permissions on the whole new wiki as those
   *  set on the template wiki.
   *
   *
   *
   */
    public function cloneWikiPermission()
    {
        $template_id = db_ei($this->template_id);
        $result      = db_query("SELECT * FROM permissions
                               where permission_type='WIKI_READ' and object_id=CAST($template_id AS CHAR CHARACTER SET utf8)");
        while ($row = db_fetch_array($result)) {
            $group_id  = db_ei($this->group_id);
            $ugroup_id = db_ei($this->getMappedUGroupId($row['ugroup_id']));
            db_query("INSERT INTO permissions (permission_type, object_id, ugroup_id)
                    VALUES ('WIKI_READ', CAST($group_id AS CHAR CHARACTER SET utf8), $ugroup_id)");
        }
    }

 /**
   *
   *  Clone permissions set on wiki pages of the template project.
   *
   *  @param hash: templatepageid => new page id.
   *
   *
   */
    public function cloneWikiPagesPermissions($array)
    {
        $result = db_query(sprintf("SELECT object_id, ugroup_id "
                                . "FROM permissions perm, wiki_page wpg "
                                . "WHERE perm.permission_type='WIKIPAGE_READ' "
                                . "AND wpg.group_id=%d "
                                . "AND perm.object_id=wpg.id", $this->template_id));

        while ($row = db_fetch_array($result)) {
            $wiki_page_clone_id = db_ei($this->getWikiPageCloneId($array, $row['object_id']));
            $ugroup_id          = db_ei($this->getMappedUGroupId($row['ugroup_id']));
            db_query("INSERT INTO permissions (permission_type, object_id, ugroup_id)"
                  . "VALUES ('WIKIPAGE_READ', CAST($wiki_page_clone_id AS CHAR CHARACTER SET utf8), $ugroup_id)");
        }
    }

 /**
   *
   *  Escape special chars in a string,  so that it is safe to place it in a
   *  mysql query.
   *
   *  @params string : the string to escape.
   *  @return string : the string escaped.
   *
   */
    public function escapeString($string)
    {
        return db_escape_string($string);
    }

 /**
   * Serialize data
   */
    public function _serialize($data)
    {
        if (empty($data)) {
            return '';
        }
        assert(is_array($data));
        return serialize($data);
    }

 /**
   * Deserialize data
   */
    public function _deserialize($data)
    {
        return empty($data) ? [] : unserialize($data);
    }
}
