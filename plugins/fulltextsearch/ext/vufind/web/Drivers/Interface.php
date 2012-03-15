<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
 
 
/**
 * Catalog Specific Driver Class
 *
 * This interface class is the definition of the required methods for
 * interacting with the local catalog.
 *
 * The parameters are of no major concern as you can define the purpose of the
 * parameters for each method for whatever purpose your driver needs.
 * The most important element here is what the method will return.  In all cases
 * the method can return a PEAR_Error object if an error occurs.
 */
interface DriverInterface
{
    public function getStatus($id);
    public function getStatuses($ids);
    public function getHolding($id);
    public function getPurchaseHistory($id);
}

?>