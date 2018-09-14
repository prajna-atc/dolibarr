<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

dol_include_once('/salesorder/class/salesobject.class.php');



/**
 * \file    salesorder/class/api_salesorder.class.php
 * \ingroup salesorder
 * \brief   File for API management of salesobject.
 */

/**
 * API class for salesorder salesobject
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SalesOrderApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'name'
    );


    /**
     * @var SalesObject $salesobject {@type SalesObject}
     */
    public $salesobject;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->salesobject = new SalesObject($this->db);
    }

    /**
     * Get properties of a salesobject object
     *
     * Return an array with salesobject informations
     *
     * @param 	int 	$id ID of salesobject
     * @return 	array|mixed data without useless information
	 *
     * @url	GET salesobjects/{id}
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->salesobject->read) {
			throw new RestException(401);
		}

        $result = $this->salesobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'SalesObject not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('salesobject',$this->salesobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->salesobject);
    }


    /**
     * List salesobjects
     *
     * Get a list of salesobjects
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
     * @throws RestException
     *
     * @url	GET /salesobjects/
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."salesobject as s";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
        $sql.= " WHERE s.fk_stcomm = st.id";

		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' AND s.entity IN ('.getEntity('salesobject').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $salesobject_static = new SalesObject($db);
                if($salesobject_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($salesobject_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve salesobject list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No salesobject found');
        }
		return $obj_ret;
    }

    /**
     * Create salesobject object
     *
     * @param array $request_data   Request datas
     * @return int  ID of salesobject
     *
     * @url	POST salesobjects/
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->salesobject->create) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->salesobject->$field = $value;
        }
        if( ! $this->salesobject->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->salesobject->id;
    }

    /**
     * Update salesobject
     *
     * @param int   $id             Id of salesobject to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT salesobjects/{id}
     */
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->salesobject->create) {
			throw new RestException(401);
		}

        $result = $this->salesobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'SalesObject not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('salesobject',$this->salesobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->salesobject->$field = $value;
        }

        if($this->salesobject->update($id, DolibarrApiAccess::$user))
            return $this->get($id);

        return false;
    }

    /**
     * Delete salesobject
     *
     * @param   int     $id   SalesObject ID
     * @return  array
     *
     * @url	DELETE salesobject/{id}
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->salesobject->supprimer) {
			throw new RestException(401);
		}
        $result = $this->salesobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'SalesObject not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('salesobject',$this->salesobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( !$this->salesobject->delete($id))
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'SalesObject deleted'
            )
        );

    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $salesobject = array();
        foreach (SalesObjectApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $salesobject[$field] = $data[$field];
        }
        return $salesobject;
    }
}
