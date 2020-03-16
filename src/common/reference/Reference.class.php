<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * Reference class
 * Stores a reference as stored in the DB (with keyword, link, etc.)
 */
class Reference
{

    /**
     * @var int the ID as stored in the 'Reference' DB table.
     */
    public $id;
    /**
     * @var string the keyword to extract.
     */
    public $keyword;
    /**
     * @var string description of this reference
     */
    public $description;

    /**
     * Originally, the 'link' contains parameters (like $1, $2) that are later converted with replaceLink()
     * @var string link pointed by this reference
     */
    public $link;

    /**
     * @var string is either 'S' for system references, or 'P' for project-defined references.
     */
    public $scope;

    /**
     * Service short name is useful to automate reference (de-)activation when (de-)activating a service.
     * @var string
     */
    public $service_short_name;

    /**
     * Nature of the referenced item.
     * List of available natures is ReferenceManager : getAvailableNatures()
     * @var string
     */
    public $nature;

    /**
     * @var bool
     */
    public $is_active;
    /**
     * @var int
     */
    public $group_id;

    /**
     * This parameter is computed from the 'link' param.
     * @var int when set
     */
    public $num_param = null;

    /**
     *
     * The constructor only builds full objects; Only the 'myid' and 'mygroup_id' params may be set to 0 if unknown.
     */
    public function __construct($myid, $mykeyword, $mydescription, $mylink, $myscope, $myservice_short_name, $nature, $myis_active, $mygroup_id)
    {
        $this->id = $myid;
        $this->keyword = strtolower($mykeyword);
        $this->description = $mydescription;
        $this->link = $mylink;
        $this->scope = $myscope;
        $this->service_short_name = $myservice_short_name;
        $this->nature = $nature;
        $this->is_active = $myis_active;
        $this->group_id = $mygroup_id;
        $this->num_param = $this->computeNumParam($this->link);
    }

    /**
     * Accessors
     */
    public function getId()
    {
        return $this->id;
    }
    public function getKeyword()
    {
        return $this->keyword;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getLink()
    {
        return $this->link;
    }
    public function getScope()
    {
        return $this->scope;
    }
    public function getServiceShortName()
    {
        return $this->service_short_name;
    }
    public function getNature()
    {
        return $this->nature;
    }
    public function isActive()
    {
        return $this->is_active;
    }
    public function getGroupId()
    {
        return $this->group_id;
    }
    /**
     * @return bool true if this is a system reference (false if project reference)
     */
    public function isSystemReference()
    {
        return ($this->scope == 'S');
    }



    /**
     * @see computeNumParam()
     */
    public function getNumParam()
    {
        // Compute number of parameters if not already done
        if ($this->num_param == false) {
            $this->num_param = $this->computeNumParam($this->link);
        }
        return $this->num_param;
    }

    public function setIsActive($my_is_active)
    {
        $this->is_active = $my_is_active;
    }

    public function setGroupId($my_group_id)
    {
        $this->group_id = $my_group_id;
    }

    public function setId($my_id)
    {
        $this->id = $my_id;
    }

    public function setDescription($my_description)
    {
        $this->description = $my_description;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

   /**
     * Replace original link with arguments
     *
     * Replacement rules
     * $projname -> project short name
     * $group_id -> project id
     * $0        -> keyword used in text
     * $1        -> first param
     * $2        -> second param, and so on until 9th param
     *
     * @param array $args array of arguments (optional)
     * @param string $projname contains the project name (optional)
    */
    public function replaceLink($args = null, $projname = null)
    {
        $this->link = str_replace('$0', $this->keyword, $this->link);
        if ($projname) {
            $this->link = str_replace('$projname', $projname, $this->link);
        }
        $this->link = str_replace('$group_id', $this->group_id, $this->link);
        if (is_array($args)) {
            $count = count($args);
            if ($count > 9) {
                $count = 9;
            }
            for ($i = 1; $i <= $count; $i++) {
                $this->link = str_replace('$' . $i, urlencode($args[$i - 1]), $this->link);
            }
        }
    }

    /**
     * Returns number of parameters needed to compute the link
     *
     * For instance, if only '$3' is used in the original link, it
     * does not mean that only one param is needed: 3 params are needed,
     * but only one is used to compute the link.
     * Max number is 9 parameters.
     *
     * @param string $link original link containing '$1', '$2',... parameters
     * @return int number of parameters needed to compute the link
     * @static
     */
    public function computeNumParam($link)
    {
        for ($i = 9; $i > 0; $i--) {
            if (strpos($link, '$' . $i) !== false) {
                return $i;
            }
        }
        return 0;
    }

    /**
     * @return ReferenceDao instance
     */
    public function &_getReferenceDao()
    {
        if (!is_a($this->referenceDao, 'ReferenceDao')) {
            $this->referenceDao = new ReferenceDao(CodendiDataAccess::instance());
        }
        return $this->referenceDao;
    }
}
