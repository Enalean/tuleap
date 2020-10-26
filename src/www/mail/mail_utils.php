<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

function mail_header($params)
{
    global $group_id, $Language;

    \Tuleap\Project\ServiceInstrumentation::increment('mailinglists');

    //required for site_project_header
    $params['group'] = $group_id;
    $params['toptab'] = 'mail';

    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    if (! $project->usesMail()) {
        exit_error($Language->getText('global', 'error'), $Language->getText('mail_utils', 'mail_turned_off'));
    }

    $list_breadcrumb = new BreadCrumb(
        new BreadCrumbLink(_('Lists'), '/mail/?group_id=' . urlencode($group_id)),
    );
    $breadcrumbs = new BreadCrumbCollection();
    $breadcrumbs->addBreadCrumb($list_breadcrumb);

    if (user_ismember($group_id, 'A')) {
        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection(
                    [
                        new BreadCrumbLink(
                            _('Add List'),
                            '/mail/admin/?' . http_build_query(
                                [
                                    'group_id' => $project->getID(),
                                    'add_list' => '1'
                                ]
                            ),
                        ),
                        new BreadCrumbLink(
                            _('Update List'),
                            '/mail/admin/?' . http_build_query(
                                [
                                    'group_id'      => $project->getID(),
                                    'change_status' => '1'
                                ]
                            ),
                        )
                    ]
                )
            )
        );
        $list_breadcrumb->setSubItems($sub_items);
    }

    $GLOBALS['HTML']->addBreadcrumbs($breadcrumbs);
    site_project_header($params);
}

function mail_footer($params)
{
    site_project_footer($params);
}

// Checks if the mailing-list (list_id) is public (return 1) or private (return 0)
function mail_is_list_public($list)
{
    $sql = sprintf(
        'SELECT is_public FROM mail_group_list' .
                      ' WHERE group_list_id = "%d"',
        $list
    );
    $res = db_query($sql);

    return db_result($res, 0, 'is_public');
}

//Checks if a mailing-list (list_id) exist and is active
function mail_is_list_active($list)
{
    $sql = sprintf(
        'SELECT status' .
                    ' FROM mail_group_list' .
                    ' WHERE group_list_id = "%d"',
        $list
    );
    $res = db_query($sql);
    if (db_numrows($res) < 1) {
        return false;
    } else {
        $status = db_result($res, 0, 'status');
        if ($status <> 1) {
            return false;
        } else {
            return true;
        }
    }
}

// Gets mailing-list name from list id
function mail_get_listname_from_list_id($list_id)
{
    $sql = sprintf(
        'SELECT list_name' .
                    ' FROM mail_group_list' .
                    ' WHERE group_list_id = %d',
        db_ei($list_id)
    );
    $res = db_query($sql);
    return db_result($res, 0, 'list_name');
}
