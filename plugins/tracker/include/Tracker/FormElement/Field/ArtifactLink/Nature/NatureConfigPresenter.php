<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Tuleap\Tracker\Config\ArtifactLinkTypePresenter;
use CSRFSynchronizerToken;

class NatureConfigPresenter
{
    public $csrf_token;
    public $desc;
    public $title;
    public $shortname_label;
    public $forward_label_label;
    public $reverse_label_label;
    public $btn_submit;
    public $btn_close;
    public $shortname_help;
    public $forward_label_help;
    public $reverse_label_help;
    public $shortname_placeholder;
    public $forward_label_placeholder;
    public $reverse_label_placeholder;
    public $create_new_nature;
    public $edit_nature;
    public $shortname_pattern;
    public $natures_usage;
    public $has_natures;
    public $sections;
    public $available_natures;
    public $edit_icon_label;
    public $edit_system_nature_title;
    public $delete_modal_title;
    public $delete_modal_submit;
    public $delete_modal_content;

    public function __construct($title, array $natures_usage, CSRFSynchronizerToken $csrf)
    {
        $this->desc                = dgettext('tuleap-tracker', 'Links between artifacts may have a type for a better semantics between artifacts. Here is the list of allowed types for this platform.');
        $this->available_natures   = dgettext('tuleap-tracker', 'Available types');
        $this->shortname_label     = dgettext('tuleap-tracker', 'Shortname');
        $this->forward_label_label = dgettext('tuleap-tracker', 'Forward label');
        $this->reverse_label_label = dgettext('tuleap-tracker', 'Reverse label');
        $this->btn_submit          = dgettext('tuleap-tracker', 'Update type');
        $this->btn_close           = dgettext('tuleap-tracker', 'Cancel');

        $this->shortname_help     = dgettext('tuleap-tracker', 'Only letters and underscore are allowed for shortname (must start with a letter though).');
        $this->forward_label_help = dgettext('tuleap-tracker', 'E.g. for the fixed_in type, a bug will be fixed in a release so the forward label will be "Fixed in".');
        $this->reverse_label_help = dgettext('tuleap-tracker', 'E.g. for the fixed_in type, a bug is fixed in a release so the reverse label will be "Fixed" (the release has "Fixed" bugs).');

        $this->shortname_placeholder     = dgettext('tuleap-tracker', 'fixed_in');
        $this->forward_label_placeholder = dgettext('tuleap-tracker', 'Fixed in');
        $this->reverse_label_placeholder = dgettext('tuleap-tracker', 'Fixed');

        $this->create_new_nature        = dgettext('tuleap-tracker', 'Add type');
        $this->edit_nature              = dgettext('tuleap-tracker', 'Edit type');
        $this->edit_icon_label          = dgettext('tuleap-tracker', 'Edit');
        $this->edit_system_nature_title = dgettext('tuleap-tracker', 'Editing system types is disallowed.');
        $this->delete_icon_label        = dgettext('tuleap-tracker', 'Delete');
        $this->cannot_delete_title      = dgettext('tuleap-tracker', 'This type can\'t be deleted because it is or has been already used.');
        $this->delete_modal_title       = dgettext('tuleap-tracker', 'Delete type');
        $this->delete_modal_submit      = dgettext('tuleap-tracker', 'Delete');
        $this->delete_modal_content     = dgettext('tuleap-tracker', 'You are about to delete a type. This action is action is irreversible. Do you confirm this deletion?');
        $this->shortname_pattern        = NatureValidator::SHORTNAME_PATTERN;

        $this->sections = new ArtifactLinkTypePresenter();

        $this->title         = $title;
        $this->natures_usage = $natures_usage;
        $this->has_natures   = count($this->natures_usage) > 0;
        $this->no_natures    = dgettext('tuleap-tracker', 'There no artifact links types');
        $this->csrf_token    = $csrf->fetchHTMLInput();
    }
}
