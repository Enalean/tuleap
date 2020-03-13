<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Codendi_Session extends PHP_Session
{

    protected $session;
    protected $session_namespace_path;
    protected $session_namespace;

    public function __construct(&$session_storage = null)
    {
        if ($session_storage === null) {
            $this->session =& parent::getSession();
        } else {
            $this->session =& $session_storage;
        }
        $this->session_namespace_path = '.';
        $this->session_namespace =& $this->session;
    }

    public function __isset($key)
    {
        return isset($this->session_namespace[$key]);
    }

    public function __get($key)
    {
        $null = null;
        return isset($this->session_namespace[$key]) ? $this->session_namespace[$key] : $null;
    }

    public function __set($key, $v)
    {
        return $this->session_namespace[$key] = $v;
    }

    public function __unset($key)
    {
        unset($this->session_namespace[$key]);
    }

    /**
     * This function unset data a the specified namespace level
     * It differs from "set" method since it allows to unset
     * @param string $namespace
     * @param string|null $key
     */
    public function remove($namespace, $key = null)
    {
        $session = &$this->getNamespace($namespace);
        if ($key !== null) {
            unset($session[$key]);
        } else {
            $session = '';
        }
    }

    /**
     * Readonly Wrapper for getNamespace
     * @param string $namespace
     * @param string $key
     */
    public function &get($namespace, $key = null)
    {
        $session = &$this->getNamespace($namespace);
        if ($key !== null) {
            if ($session[$key]) {
                return $session[$key];
            }
            return '';
        }
        return $session;
    }

    /**
     * Changes a given namespace value
     * @param string $namespace
     * @param mixed $value
     */
    public function set($namespace, $value)
    {
        $session = &$this->getNamespace($namespace, true);
        $session = $value;
    }
    /**
     * Allows one get a subtree of the session by addressing it with dotted path my.sub.tree :)
     * @param string $namespace
     * @return mixed
     */
    public function &getNamespace($namespace, $create_path = false)
    {
        $session = &$this->getSessionNamespace();
        //empty namespace
        if (empty($namespace)) {
            return $session;
            //throw new Exception('ERROR - Empty session namespace');
        }
        //only array can be iterated
        if (!is_array($session)) {
            return $session;
        }
        $pathway = explode('.', $namespace);
        $count   = count($pathway);
        $i = 0;
        foreach ($pathway as $path) {
                $i = $i + 1;
                //last path element not reached yet <=> wrong path
            if (!$create_path && $i < $count && ((is_array($session) && !isset($session[$path])) || !is_array($session) || !is_array($session[$path]) )) {
                $r = null;
                return $r;
            }

                //only array can be iterated
            if (!is_array($session)) {
                return $session;
            }
            if (!isset($session[$path])) {
                if ($create_path) {
                    $session[$path] = array();
                } else {
               //path does not exist and we do not want to create it
                    $r = null;
                    return $r;
                }
            }
            $session = &$session[$path];
        }
        return $session;
    }

    /**
     * clean a given namespace
     * @todo pass namespace as argument, make a safe clean global session way
     */
    public function cleanNamespace()
    {
        $this->session_namespace = '';
    }

    public function &getSessionNamespace()
    {
        return $this->session_namespace;
    }

    /**
     * !! WARNING !! : never use this in your code, it is only designed for unit testing
     * Set the current session namespace
     * @param string $session_namespace
     */
    public function setSessionNamespace(&$session_namespace)
    {
        $this->session_namespace = &$session_namespace;
    }

    /**
     * Change global session namespace (only goes down into the tree)
     * @param string $namespace
     */
    public function changeSessionNamespace($namespace)
    {
        if (strpos($namespace, '.') === 0) {
            //absolute path
            $this->session_namespace_path = $namespace;
            $this->session_namespace = &$this->session;
            $namespace = substr($namespace, 1);
        } else {
            //relative path (down the tree)
            if ($this->session_name_path != '.') {
                $this->session_namespace_path .= '.' . $namespace;
            } else {
                $this->session_namespace_path .= $namespace;
            }
        }
        $this->session_namespace = &$this->getNamespace($namespace, true);
    }

    /**
     * Gives the absolute session path
     * @return string
     */
    public function getSessionNamespacePath()
    {
        return $this->session_namespace_path;
    }

    /**
     * !! WARNING !! Unit testing only
     */
    public function setSessionNamespacePath($namespace)
    {
        $this->session_namespace_path = $namespace;
    }
}
