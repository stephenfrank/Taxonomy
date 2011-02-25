<?php
/*
 * Originally Created on 2007 sep 21
 * by Martin Wernstahl <m4rw3r@gmail.com>
 * Additional functions created by Iain Urquhart
 */
/*
    This file is part of MPTtree.

    MPTtree is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    MPTtree is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * @addtogroup MPTtree
 * @{
 * A class to handle modified preorder tree traversal (nested sets) trees.
 * 
 * Needed columns: lft, rgt and id (optional is title, which is used by the display() method
 * and by the xpath() method),
 * all these can be changed using the set_opts() method
 * 
 * For more information read the MPTtree Usage Manual or the Change log
 * 
 * @attention Uses the CI Active record class
 * 
 * @note Have ONLY been tested with MySQL 5
 * 
 * @version 0.1.6-fix
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * @par License:
 * Released under the GNU Lesser General Public License (LGPL), see @ref LGPL and @ref GPL
 * 
 * @par Thanks to
 * Hamish Guthrie <hamish@prodigi.ch> (Table locking)
 * 
 * @par PHP Version
 * PHP 4 or greater\n
 * Iterators require PHP 5 or greater
 */
 
/**
 * @page MPTtree_Change_log Change Log
 * @anchor MPTtree_ChangeLog
 * <hr>
 * @section sec Version History
 * @section sec8 0.1.6-fix2
 * <ul>
 *     <li>Removed two unnessesary queries, from <dfn>move_node()</dfn> and <dfn>insert_node()</dfn>
 *     <li>Fixed a bug in <dfn>move_node()</dfn> that makes it return wrong values (and cause php warnings).</li>
 *     <li>Removed two unnessesary queries from <dfn>delete_node()</dfn>.</li>
 * </ul>
 * @section sec7 0.1.6-fix
 * <ul>
 *     <li>Fixed error with call to <dfn>db::get_where()</dfn> in <dfn>move_node()</dfn></li>
 *     <li>Corrected return values from <dfn>move_node()</dfn>, now it returns a normal array, just like <dfn>insert_node()</dfn></li>
 *     <li><dfn>tree2array()</dfn> now returns false if the root node cannot be found</li>
 *     <li>Added some debug messages</li>
 *     <li>Renamed <kbd>MPTtree.php</kbd> to <kbd>mpttree.php</kbd> to avoid include errors under linux</li>
 *     <li>Minor corrections in the manual</li>
 * </ul>
 * @section sec6 0.1.6
 * <ul>
 *     <li>Added <dfn>tree2array()</dfn></li>
 *     <li>Added <dfn>get_descendants_wlevel()</dfn></li>
 *     <li>Added <dfn>has_children()</dfn></li>
 *     <li>Added <dfn>has_children()</dfn> to ORM object</li>
 *     <li>Added command swith to <dfn>get_descendants()</dfn> to make it call <dfn>get_descendants_wlevel()</dfn> instead, thus returning a level column</li>
 *     <li><dfn>get_ORM()</dfn> can now also accept a path (string or array doesn't matter) to the node which to load.</li>
 * </ul>
 * @section sec5 0.1.5
 * <ul>
 *     <li>Introducing an ORM wrapper for MPTtree</li>
 *     <li>Added a MPTtreeIterator, to iterate over descendants in the tree</li>
 *     <li>Added a MPTtreeORMIterator, to iterate over descendants in the tree, returning ORM objects</li>
 *     <li>Added <dfn>get_node_byid()</dfn></li>
 *     <li>Improved <dfn>get_parent()</dfn></li>
 *     <li>Improved <dfn>count_children()</dfn>, not as much database traffic and counting is done in database</li>
 *     <li>Improved <dfn>xpath()</dfn>, hopefully faster and more accurate (doesn't need <dfn>xpath2()</dfn>)</li>
 *     <li>Changed the return values from the insert and move methods to return the new lft and rgt values instead of true.</li>
 *     <li><dfn>xpath()</dfn> and <dfn>xpath2()</dfn> can now also accept an array as path (no need using separators)</li>
 * </ul>
 * @section sec4 0.1.4
 * <ul>
 *     <li>Added a few methods to be used with Active Record</li>
 *     <li>Added calls to <dfn>db::protect_identifiers()</dfn> in <dfn>set_opts()</dfn>
 *     and in <dfn>set_table()</dfn></li>
 *     <li>Replaced the method <dfn>xpath()</dfn> with a much more efficient xpath variant.</li>
 *     <li>The old <dfn>xpath()</dfn> method have been renamed to <dfn>xpath2()</dfn>.</li>
 *     <li>HCG: Replaced use of transactions with locks</li>
 * </ul>
 * @section sec3 0.1.2
 * <ul>
 *     <li>Added the method <dfn>xpath()</dfn></li>
 *     <li>Remade <dfn>set_opts()</dfn> to accept an array with the parameters</li>
 *     <li>Added a sanitation of the data to <dfn>update_node()</dfn> and to the insert methods.
 *         This to prevent damage to the tree.</li>
 * </ul>
 * @section sec2 0.1.1
 * <ul>
 *     <li>Added transaction support</li>
 *     <li>Added the method <dfn>get_descendants_where()</dfn></li>
 *     <li>Added the method <dfn>get_children_where()</dfn></li>
 *     <li>Changed algohrithm for <dfn>get_children()</dfn>, the new is almost twice as fast as the old one!</li>
 *     <li>Renamed some internal properties to address some conflicts with different CI libraries</li>
 *     <li>Changed name of <dfn>get_decendants()</dfn> to <dfn>get_descendants()</dfn>,
 *     a bad misspelling by me.</li>
 * </ul>
 * @section sec1 0.1
 * <ul>
 *    <li><b>Initial Release</b></li>
 * </ul>
 */

/**
 * The model class.
 * @version 0.1.6
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * @par License:
 * Released under the GNU Lesser General Public License (LGPL), see @ref LGPL and @ref GPL
 * @since 0.0.1
 * 
 * This class can be extended if you want to have multiple instances of MPTtree running.
 * Just override the generic settings variables you want to change (if you override, you can skip set_table() / set_opts())\n
 * Example:
 * @code
 * class Page_model extends MPTtree{
 *     var $tree_table = 'pages'; // only tree_table this time, thus we need to call set_opts() if we want to change something else (no need to use set_table() though)
 *
 *     function Page_model(){
 *         parent::MPTtree();
 *     }
 * }
 * @endcode
 * @attention You must set the table with set_table() or set_opts() before operating on the tree (if $tree_table isn't set).
 */
class MPTtree {
	/**
	 * The table which contains the tree.
	 */
	var $tree_table;
	/**
	 * The column which stores the lft value.
	 * Column would preferably be of type UNSIGNED INT
	 */
	var $left_col = 'lft';
	/**
	 * The column which stores the rgt value.
	 * Column would preferably be of type UNSIGNED INT
	 */
	var $right_col = 'rgt';
	/**
	 * The column which stores the unique id.
	 * Column would preferably be of type UNSIGNED INT, AUTO INCREMENTAL
	 * @note MPTtree does not assign this column any value, hence the prefered AUTO INCREMENT
	 */
	var $id_col = 'id';
	/**
	 * The column which stores the node titles.
	 * Used by display(), xpath(), xpath2() and MPTtree_ORM_node::path()
	 */
	var $title_col = 'title';
	/**
	 * Regulates debugging of MPTtree.
	 */
	var $debug_on = false;
	/**
	 * The dir where ORM and iterator include files are situated.
	 * @note With leading slash and Without trailing slash.
	 * @note relative to APPPATH constant (defined by CI)
	 */
	static $inc_dir = 'third_party/taxonomy/libraries/MPTtree';
	/**
	 * Variable to determine if ORM file has been loaded.
	 */
	static $ORM_enabled = false;
	/**
	 * Variable to determine if iterator file has been loaded.
	 */
	static $iterator_enabled = false;
	
	/**
	 * Constructor.
	 */
	function MPTtree(){
	
		$this->EE =& get_instance();
		
		$this->base	 	 	= (defined('BASE')) ? BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy' : '';
		
		$this->form_base 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy';
		$this->theme_base 	= $this->EE->config->item('theme_folder_url').'third_party/taxonomy_assets/';
		$this->site_id	 	= $this->EE->config->item('site_id');
		
		
	}
	
	/**
	 * Changes the options used for this class.
	 * @since 0.1
	 * @param $opts An array with the options,
	 * key = optionname and value = optionvalue.\n
	 * Available options:
	 * @arg <b>table</b> The table which holds the tree, default: null
	 * @arg <b>left</b> The name of the column which holds the lft value, default: 'lft'
	 * @arg <b>right</b> The name of the column which holds the rgt value, default: 'rgt'
	 * @arg <b>id</b> The name of the column which holds the unique id, default: 'id'
	 * @arg <b>title</b> The name of the title column (used by xpath() and display()), default: 'title'
	 * @return void
	 */
	function set_opts($opts){
		$table = isset($opts['table']) ? $opts['table'] : null;
		$left  = isset($opts['left'])  ? $opts['left']  : 'lft';
		$right = isset($opts['right']) ? $opts['right'] : 'rgt';
		$id    = isset($opts['id'])    ? $opts['id']    : 'id';
		$title = isset($opts['title']) ? $opts['title'] : 'title';
		$this->set_table($table);
		$this->left_col = $left;
		$this->right_col = $right;
		$this->id_col = $id;
		$this->title_col = $title;
	}
	
	/**
	 * Changes the table which the class operates on.
	 * @since 0.1
	 * @param $table_name The name of the new table
	 * @return void
	 */
	function set_table($table_name){
		if($table_name != null || $table_name != ''){
			$this->tree_table = $table_name;
			return;
		}
		log_message('warning','MPTtree: The table name was not correctly set.');
	}
	
	//////////////////////////////////////////
	//  Iterator functions
	//////////////////////////////////////////
	
	/**
	 * Enables iterator support by including file with iterator.
	 * Preferred Usage:
	 * @code
	 * if(!$this->enable_iterator())
	 *      return false; // the method requiring iterators to be available exits with false
	 * @endcode
	 * @since 0.1.5
	 * @return true if included, false if PHP version is less than 5
	 */
	function enable_iterator(){
		if(floor(phpversion()) < 5){
			show_error('MPTtree: PHP version 5 or greater is required to use the Iterator interface.');
			return false;
		}
		if(!self::$iterator_enabled){
			require_once(APPPATH.self::$inc_dir.'/MPTtree_iterator.php');
			self::$iterator_enabled = true;
		}
		return true;
	}
	
	/**
	 * Returns a MPTtreeIterator object to iterate over the whole tree.
	 * (or the descendants of a specified node)\n
	 * Enables iterator support if it isn't already.
	 * @note Returns an iterator which returns asociative arrays
	 * @since 0.1.5
	 * @param $lft The lft of the node to iterate (if set to 0 (default), the whole tree will be iterated)
	 * @note Requires PHP version 5 or higher
	 * @return A MPTtreeIterator if PHP version > 5, otherwise false
	 */
	function iterator($lft = 0){
		if(!$this->enable_iterator())
			return false;
		return new MPTtreeIterator($this,$lft);
	}
	
	/**
	 * Returns a MPTtreeORMIterator object to iterate over the whole tree.
	 * (or the descendants of a specified node)\n
	 * Enables iterator support if it isn't already.
	 * @note Returns an iterator which returns ORM objects
	 * @since 0.1.5
	 * @param $lft The lft of the node to iterate (if set to 0 (default), the whole tree will be iterated)
	 * @note Requires PHP version 5 or higher
	 * @return A MPTtreeORMIterator if PHP version > 5, otherwise false
	 */
	function ORMiterator($lft = 0){
		if(!$this->enable_iterator())
			return false;
		return new MPTtreeORMIterator($this,$lft);
	}
	
	//////////////////////////////////////////
	//  ORM functions
	//////////////////////////////////////////
	
	/**
	 * Enables ORM support.
	 * @since 0.1.5
	 */
	function enable_ORM(){
		if(!self::$ORM_enabled){
			require_once(APPPATH.self::$inc_dir."/MPTtree_ORM.php");
			self::$ORM_enabled = true;
		}
	}
	
	/**
	 * Returns a populated ORM object.
	 * Activates ORM if it isn't already.\n
	 * If the node does not exist, false is returned.
	 * @since 0.1.5
	 * @param $lft The lft value of the reqested node, or a path to the requested node (array or string (separator = '/))
	 * @return A MPTtree_ORM_noce object if node exists, false otherwise
	 */
	function get_ORM($lft = 1){
		$this->enable_ORM();
		// find by id
		if(is_numeric($lft)){
			if(!($node = $this->get_node($lft)))
				return false;
		}
		// find by path
		else if(is_string($lft) || is_array($lft)){
			if(!($node = $this->xpath($lft)))
				return false;
		}
		else{
			log_message('error','Couldn\'t load node with path/id '.$lft.' because it is not numeric, a string or an array.');
		}
		return new MPTtree_ORM_node($this,$node);
	}
	
	/**
	 * Returns a populated ORM object, using id column to find the node.
	 * Activates ORM if it isn't already.
	 * If the node does not exist, false is returned.
	 * @since 0.1.6
	 * @return A MPTtree_ORM_noce object if node exists, false otherwise
	 */
	function get_ORM_byid($id = 1){
		$this->enable_ORM();
		if(!($node = $this->get_node_byid($id)))
			return false;
		return new MPTtree_ORM_node($this,$node);
	}
	
	/**
	 * Creates a new empty ORM object to be inserted into the tree.
	 * Activates ORM if it isn't already.
	 * @since 0.1.5
	 * @return A new MPTtree_ORM_node object
	 */
	function new_ORM(){
		$this->enable_ORM();
		return new MPTtree_ORM_node($this,false);
	}
	
	//////////////////////////////////////////
	//  Lock functions
	//////////////////////////////////////////

	/**
	 * Locks tree table.
	 * This is a straight write lock - the database blocks until the previous lock is released
	 * @since 0.1.4
	 */
	function lock_tree_table($aliases = array())
	{
		$q = "LOCK TABLE " . $this->tree_table . " WRITE";
		$res = $this->EE->db->query($q);
	}

	/**
	 * Unlocks tree table.
	 * Releases previous lock
	 * @since 0.1.4
	 */
	function unlock_tree_table()
	{
		$q = "UNLOCK TABLES";
		$res = $this->EE->db->query($q);
	}
	
	///////////////////////////////////////////////
	//  Get functions
	///////////////////////////////////////////////
	
	/**
	 * Returns the root node object.
	 * @since 0.1
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_root(){
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => 1),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		return $return;
	}
	
	/**
	 * Returns the node with lft value of $lft.
	 * @since 0.1
	 * @param $lft The lft of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node($lft){
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => $lft),1);
		$return = $query->num_rows() ? $query->row_array() : false;

		return $return;
	}
	
	/**
	 * Returns the node with id value of $id.
	 * @since 0.1.5
	 * @param $id The id of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node_byid($id){
		$query = $this->EE->db->get_where($this->tree_table,array($this->id_col => $id),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		if(!$return)
			echo 'Node with '.$this->id_col.' '.$id.' was not found.';
		return $return;
	}
	
	/**
	 * Returns all descendants to the node with the value lft and rgt.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $with_level_col If the results shall reflect the relative level the nodes lie on
	 * @note With the with_level_col switch the method is a bit slower because the database must do some extra processing
	 * @see get_descendants_wlevel()
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_descendants($lft,$rgt,$with_level_col = false){
		if($with_level_col)
			return $this->get_descendants_wlevel($lft,$rgt);
		$this->EE->db->where($this->left_col.' >',$lft);
		$this->EE->db->where($this->right_col.' <',$rgt);
		$this->EE->db->order_by($this->left_col,'asc');
		$query = $this->EE->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	/**
	 * Returns all descendants to the node with lft $lft and rgt $rgt,
	 * with an extra column that contains the relative level.
	 * @note Primarily for internal use, use the with_level_col modifier on get_descendants() instead
	 * @since 0.1.6
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return A multidimensional accociative array with the table rows,
	 * with a column (depth) added,
	 * but if no rows returned, empty array
	 */
	function get_descendants_wlevel($lft,$rgt){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
			
		$result = $this->EE->db->query(
"SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
			{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
			AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth > 0
ORDER BY node.{$this->left_col};");
		return $result->num_rows() ? $result->result_array() : array();
	}
	
	/**
	 * Returns all descendants to the node with the lft lft and the rgt rgt, filtered by the $where parameter.
	 * @note The where parameter is passed to the where() method in CodeIgniters Active Record class.
	 * @since 0.1.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $where The where filter of the query, sent to the where() method of the Active Record class
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_descendants_where($lft,$rgt,$where){
		$this->EE->db->where($this->left_col.' >',$lft);
		$this->EE->db->where($this->right_col.' <',$rgt);
		$this->EE->db->where($where);
		$this->EE->db->order_by($this->left_col,'asc');
		$query = $this->EE->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	/**
	 * Returns the number of descendants a node has.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return an int with the num of descendants
	 */
	function count_descendants($lft,$rgt){
		return (($rgt - $lft) - 1) / 2;
	}
	
	/**
	 * Returns all children of the node with the values lft and rgt.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_children($lft,$rgt){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
			
		$result = $this->EE->db->query(
"SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
			{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
			AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col};");
		return $result->num_rows() ? $result->result_array() : array();
	}
	
	/**
	 * Returns all children to the node with the lft lft and rgt rgt, filtered by the $where parameter.
	 * @since 0.1.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @param $where The where filter of the query, sent to the where() method of the Active Record class
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_children_where($lft,$rgt,$where){
		if($rgt - $lft < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			return array();
		$this->EE->db->select('*');
		// Circumvent the db escaping to enable a subquery
		$this->EE->db->ar_from[] =
"(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a";
		$this->EE->db->where($where);
		$result = $this->EE->db->get();
		$children = $result->result_array();
		return count($children)? $children : array();
	}
	
	/**
	 * Returns the number of children a node has.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return an int with the num of children
	 */
	function count_children($lft,$rgt){
		$result = $this->EE->db->query(
"SELECT COUNT(*) as num FROM
(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a");
		$result = $result->row_array();
		return $result['num'];
	}
	
	/**
	 * A method for determining if a node has children.
	 * @since 0.1.6
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return true or false, depending on result
	 */
	function hasChildren($lft,$rgt){
		return (($rgt - $lft) > 1);
	}
	
	/**
	 * Returns all parents to a node (grand parents and grandgrand parents and so on).
	 * Index 0 is the closest parent.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return A multidimensional asociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_parents($lft,$rgt){
		$this->EE->db->where($this->left_col.' <',$lft);
		$this->EE->db->where($this->right_col.' >',$rgt);
		$this->EE->db->order_by($this->left_col,'desc');
		$query = $this->EE->db->get($this->tree_table);
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	/**
	 * Returns the closest related parent.
	 * @since 0.1
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return An asociative array with the table rows,
	 * but if no rows returned, false
	 */
	function get_parent($lft,$rgt){
		$this->EE->db->where($this->left_col.' <',$lft);
		$this->EE->db->where($this->right_col.' >',$rgt);
		$this->EE->db->order_by($this->left_col,'desc');
		$this->EE->db->limit(1); // we only want the first of all parents
		$query = $this->EE->db->get($this->tree_table);
		return $query->num_rows() ? $query->row_array() : false;
	}
	
	/**
	 * Returns the node at the end of the path $path.
	 * This is the improved version, it is more than 
	 * twice as fast as the old (xpath2()).
	 * But this performance comes at a price: \n
	 * It is not as accurate as xpath2(). \n
	 * If you have two paths like this: \n
	 * /fruit/green/apple \n
	 * /green/apple \n
	 * And if you search on /green/apple, xpath will find both.
	 * If xpath gets more than one row from the database,
	 * it will try to match the number of segemnts with the number of parents.\n
	 * So if you know you are going to have many paths like this
	 * in your tree, it may be better using xpath2().
	 * @since 0.1.2
	 * @param $path The path to the node (can be an array), separated by '/' (not needed if an array)
	 * @param $separator The separator between the segments, default: '/'
	 * @param $root The lft of the node to be root in the query, default: 1
	 * @note The path to the rootnode is only a '/', so
	 * the path to a child is '/childtitle', and to a grandchild:
	 * '/childtitle/grandchildtitle'
	 * @return An array with the requested node's data
	 */
	function xpath($path,$separator = '/',$root = 1){
		if(is_array($path)){
			$segments = $path;
		}
		else{
			// split the segments
			$segments = explode($separator,$path);
		}
		// load the rootnode
		$this->EE->db->select($this->left_col.', '.$this->right_col);
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => $root));
		if(!$query->num_rows()){
			log_message('error','MPTtree: xpath() cannot find node with '.
								$this->left_col.': '.$root.', aborting xpath().');
			return false;
		}
		$root_node = $query->row_array();
		// construct query
		$from_str = "(SELECT * FROM {$this->tree_table} WHERE {$this->left_col} = {$root_node[$this->left_col]}) as parent0";
		$where_str = '';
		// iterate the segments
		$j = 1;
		foreach($segments as $part){
			if($part != null || $part != ''){
				$from_str .= ",\n".$this->tree_table.' as parent'.$j;
				if($j > 1)
					$where_str .= 'AND ';
				
				$where_str .= "parent$j.{$this->left_col} BETWEEN parent".($j-1).
						".{$this->left_col} AND parent".($j-1).
						".{$this->right_col} AND parent$j.{$this->title_col} = '$part'\n";
				$j++;
			}
		}
		// $j = number of real segments + 1 (root node) + 1
		// last node
		$query = $this->EE->db->query("SELECT parent".($j < 1 ? 0 : ($j-1)).".* FROM\n$from_str".($j == 1 ? '' : " WHERE\n$where_str"));
		// If we have multiple matches, determine which one is right
		$return = false; // default return
		foreach($query->result_array() as $node){
			// count the number of parents to a given node (with respect to the root)
			$this->EE->db->select('title');
			$this->EE->db->from($this->tree_table);
			$this->EE->db->where($this->left_col . ' <',$node[$this->left_col]);
			$this->EE->db->where($this->right_col . ' >',$node[$this->right_col]);
			$this->EE->db->where($this->left_col . ' >',$root_node[$this->left_col]);
			$this->EE->db->where($this->right_col . ' <',$root_node[$this->right_col]);
			$q = $this->EE->db->get();
			// the number of parents should match $j - 2, which is the number of real segments
			// or if $j == 1, it is the root node which has been requested
			if($q->num_rows() == $j - 2 || $j == 1){
				// We have our match
				$return = $node;
				break;
			}
		}
		return $return;
	}
	
	/**
	 * Returns the node at the end of the path $path.
	 * @deprecated
	 * @since 0.1.4
	 * @param $path The path to the node (can be an array), separated by '/' (not needed if an array)
	 * @param $separator The separator between the segments, default: '/'
	 * @param $root The lft of the node to be root in the query, default: 1
	 * @note The path to the rootnode is only a '/', so
	 * the path to a child is '/childtitle', and to a grandchild:
	 * '/childtitle/grandchildtitle'
	 * @return An array with the requested node's data
	 */
	function xpath2($path,$separator = '/',$root = 1){
		if(is_array($path)){
			$segments = $path;
		}
		else{
			// split the segments
			$segments = explode($separator,$path);
		}
		// load the rootnode
		$this->EE->db->select($this->left_col.', '.$this->right_col);
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => $root));
		if(!$query->num_rows()){
			log_message('error','MPTtree: xpath2() cannot find node with '.
								$this->left_col.': '.$root.', aborting xpath2().');
			return false;
		}
		$current_node = $query->row_array();
		// iterate the segments
		foreach($segments as $part){
			if($part != null || $part != ''){
				// We have a segment, try to match it to the children

// Almost the same as in get_children_where(), but it selects only the needed data (lft and title)
				$this->EE->db->select('*');
				// Circumvent the db escaping to enable a subquery
				$this->EE->db->ar_from[] = "(SELECT node.{$this->left_col},
node.{$this->title_col}, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
{$this->tree_table} AS parent,
{$this->tree_table} AS sub_parent,
(
	SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
	FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent
	WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col}
		AND parent.{$this->right_col} AND node.{$this->left_col} = {$current_node[$this->left_col]}
	GROUP BY node.{$this->id_col}
	ORDER BY node.{$this->left_col}
)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as a";
				$this->EE->db->where($this->title_col,$part);
				$query = $this->EE->db->get();
				// Have we got a result?
				if(!$query->num_rows()){
					log_message('error','MPTtree: xpath2() cannot find node with '.
										$this->title_col.' : '.$part.' in path:'.$path.', aborting.');
					return false;
				}
				$current_node = $query->row_array();
			}
		}
		return $this->get_node($current_node[$this->left_col]);
	}
	
	/**
	 * Returns all nodes in the path $path.
	 * @since 0.1.5
	 * @param $path The path to the node, separated by '/'
	 * @param $separator The separator between the segments, default: '/'
	 * @return An array with the root at index 0 and the last node in the path at the last index
	 */
	function fetch_path($path, $separator = '/'){
		$node = $this->xpath($path, $separator);
		if($node == false){
			return false;
		}
		return array_merge(array_reverse($this->get_parents($node[$this->left_col],$node[$this->right_col])),array($node));
	}
	
	/**
	 * Converts the tree structure in the tree to an array.
	 * Array Example:
	 * @code
	 * Array([0] => Array([id] => 1,
	 *                    [lft] => 1,
	 *                    [rgt] => 4,
	 *                    [children] => Array(
	 *                        [0] => Array([id] => 2,
	 *                                     [lft] => 2,
	 *                                     [rgt] => 3
	 *                                    )
	 *                    )
	 *      )
	 * )
	 * @endcode
	 * @since 0.1.6
	 * @param $root The node that shall be root in the tree (local scope)
	 * @return A recursive array, false if the root node was not found
	 */
	function tree2array($root = 1){
		$node = $this->get_node($root);
		if($node == false)
			return false;
		// query
		$query = $this->EE->db->query('SELECT * FROM '.$this->tree_table.
			' WHERE '.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' ORDER BY '.$this->left_col.' ASC');
		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$lastlevel = 0;
		foreach($query->result_array() as $row){
			// go more shallow, if needed
			if(count($right)){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $lastlevel){
				end($current);
				$current[key($current)]['children'] = array();
				$stack[count($right)] =& $current[key($current)]['children'];
			}
			// the stack contains all parents, current and maybe next level
			$current =& $stack[count($right)];
			// add the data
			$current[] = $row;
			// go one level deeper with the index
			$lastlevel = count($right);
			$right[] = $row[$this->right_col];
		}
		return $result;
	}
	
	//////////////////////////////////////////
	//  Active Record helpers
	//////////////////////////////////////////
	
	/**
	 * Puts the get_descendants() query in an Actve Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias descendant (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @code
	 * $this->EE->db->select('descendant.title, c.name');
	 * $this->MPTtree->AR_get_descendants(3,10); // puts the subquery which fetches all descendants to the node
	 * $this->EE->db->from('customers AS c');
	 * $this->EE->db->where('c.id = descendant.customer_id');
	 * $this->EE->db->order_by('c.name');
	 * $nodes = $this->EE->db->get();
	 * @endcode
	 * @since 0.1.4
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return void
	 */
	function AR_from_descendants_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->EE->db->ar_from[] = "(SELECT * FROM {$this->tree_table}
WHERE {$this->left_col} > $lft AND {$this->right_col} < $rgt
ORDER BY {$this->left_col} ASC) as descendant";
	}
	
	/**
	 * Puts the get_children() query in an Active Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias child (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @code
	 * $this->EE->db->select('title');
	 * $this->MPTtree->AR_get_children(3,10); // puts the subquery which fetches all children to the node
	 * $this->EE->db->order_by('title');
	 * $nodes = $this->EE->db->get();
	 * @endcode
	 * @since 0.1.4
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return void
	 */
	function AR_from_children_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->EE->db->ar_from[] = "(SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
FROM {$this->tree_table} AS node,
	{$this->tree_table} AS parent,
	{$this->tree_table} AS sub_parent,
	(
		SELECT node.{$this->id_col}, (COUNT(parent.{$this->id_col}) - 1) AS depth
		FROM {$this->tree_table} AS node,
		{$this->tree_table} AS parent
		WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
    AND node.{$this->left_col} = {$lft}
		GROUP BY node.{$this->id_col}
		ORDER BY node.{$this->left_col}
	)AS sub_tree
WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
	AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
	AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
GROUP BY node.{$this->id_col}
HAVING depth = 1
ORDER BY node.{$this->left_col}) as child";
	}
	
	/**
	 * Puts the get_parents() query in an Active Record subquery, so you can use sorting, custom filtering etc.
	 * Should be used in conjunction with CodeIgniter's Active Record class.
	 * Adds the subquery in the from block, under the alias parent (can be omitted if
	 * it's only one query/table in the FROM part of the query).
	 * @since 0.1.4
	 * @param $lft The lft value of the node
	 * @param $rgt The rgt value of the node
	 * @return void
	 */
	function AR_from_parents_of($lft,$rgt){
		// Circumvent the db escaping to enable a subquery
		$this->EE->db->ar_from[] = "(SELECT * FROM {$this->tree_table}
WHERE {$this->left_col} < $lft AND {$this->right_col} > $rgt
ORDER BY {$this->left_col} DESC) as parent";
	}
	
	//////////////////////////////////////////
	//  Update functions
	//////////////////////////////////////////
	
	/**
	 * Updates the node values.
	 * Uses the codeigniter db->update() function, so all values
	 * in the data array are to be an asociative array, ex:
	 * @code
	 * update_node(1,array('title'=>'Home Page',
	 *       'url'=>'http://webpage.com')); // will generate this sql
	 * // UPDATE SET title = 'Home Page', SET url='http://webpage.com' WHERE lft = 1
	 * @endcode
	 * @since 0.1
	 * @param $lft The lft of the node to be manipulated
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return true if success, false otherwise
	 */
	function update_node($lft,$data){
		if(!$this->get_node($lft))return false;
		$data = $this->sanitize_data($data);
		// Make the update
		$this->EE->db->where($this->left_col,$lft);
		$this->EE->db->update($this->tree_table,$data);
		return true;
	}
	
	//////////////////////////////////////////
	//  Insert functions
	//////////////////////////////////////////
	
	/**
	 * Creates the root node in the table.
	 * @since 0.1
	 * @param $data The rootnode data
	 * @return true if success, but if rootnode exists, it returns false
	 */
	function insert_root($data){
		$this->lock_tree_table(); // Lock table first then check if root exits - I am being pedantic in the sequence of these statements.
		if($this->get_root() != false) {
			$this->unlock_tree_table();
			return false;
		}
		$data = $this->sanitize_data($data);
		$data = array_merge($data,array($this->left_col => 1,$this->right_col => 2));
		$this->EE->db->insert($this->tree_table,$data);
		$this->unlock_tree_table();
		return true;
	}
	
	/**
	 * Inserts the node before the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted before
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node_before($lft,$data){
		if(!$this->get_node($lft))
			return false;
		return $this->insert_node($lft,$data);
	}
	
	/**
	 * Inserts the node after the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted before
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node_after($lft,$data){
		$node = $this->get_node($lft);
		if(!$node)
			return false;
		return $this->insert_node($node[$this->right_col] + 1,$data);
	}
	
	/**
	 * Inserts the node as the first child of the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (asociative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node($lft,$data){
		if(!$this->get_node($lft))
			return false;
		return $this->insert_node($lft + 1,$data);
	}
	
	/**
	 * Inserts the node as the last child the node with the lft specified.
	 * @since 0.1
	 * @param $lft The lft of the node to be parent
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function append_node_last($lft,$data){
		$node = $this->get_node($lft);
		if(!$node)
			return false;
		return $this->insert_node($node[$this->right_col],$data);
	}
	
	/**
	 * Inserts a child to the parent with lft $lft and sorts it after the title column.
	 * It orders the nodes in ascending order.
	 * @since 0.1.4
	 * @param $lft The lft value of the parent node.
	 * @param $data The data to be inserted.
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_sorted($lft,$data){
		// set up locks for BOTH the SELECT and the INSERT queries
		$q = "LOCK TABLE " . $this->tree_table . " WRITE, " .
			  $this->tree_table . " AS node READ, " .
			  $this->tree_table . " AS parent READ, " .
			  $this->tree_table . " AS st_node READ, " .
			  $this->tree_table . " AS st_parent READ, " .
			  $this->tree_table . " AS sub_parent READ";
		$res = $this->EE->db->query($q);
		
		$node = $this->get_node($lft);
		if(!$node){
			$this->unlock_tree_table();
			return false;
		}
		
		$children = array();
		
		if($node['rgt'] - $node['lft'] < 3) // leaf node, 3 here because of the possibility of a gap (4 = have children)
			$children = array();
		else {
			$q = "SELECT node.*, (COUNT(parent.{$this->id_col}) - (sub_tree.depth + 1)) AS depth
				  FROM 	{$this->tree_table} AS node, 
						{$this->tree_table} AS parent, 
						{$this->tree_table} AS sub_parent,
							(SELECT st_node.{$this->id_col}, (COUNT(st_parent.{$this->id_col}) - 1) AS depth
							 FROM	{$this->tree_table} AS st_node,
									{$this->tree_table} AS st_parent
							 WHERE st_node.{$this->left_col} BETWEEN st_parent.{$this->left_col} AND st_parent.{$this->right_col}
							 AND st_node.{$this->left_col} = {$lft}
							 GROUP BY st_node.{$this->id_col}
							 ORDER BY st_node.{$this->left_col}
							)AS sub_tree
				  WHERE node.{$this->left_col} BETWEEN parent.{$this->left_col} AND parent.{$this->right_col}
				  AND node.{$this->left_col} BETWEEN sub_parent.{$this->left_col} AND sub_parent.{$this->right_col}
				  AND sub_parent.{$this->id_col} = sub_tree.{$this->id_col}
				  GROUP BY node.{$this->id_col}
				  HAVING depth = 1
				  ORDER BY node.{$this->left_col};";
			$result = $this->EE->db->query($q);
			$children = $result->num_rows() ? $result->result_array() : array();
		}
			
		// set default
		$insert_lft = $node[$this->right_col];
		if (count($children)){
			foreach($children as $child){
				if(strcmp($data[$this->title_col], $child[$this->title_col]) < 0){
					$insert_lft = $child[$this->left_col];
					break;
				}
			}
		}

		$ret = $this->insert_node($insert_lft,$data, false);
		$this->unlock_tree_table();
		return $ret;
	}
	
	/**
	 * Inserts a node at the lft specified.
	 * Primarily for internal use.
	 * @since 0.1
	 * @param $lft The lft of the node to be inserted
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @param $lock If the method needs to aquire a lock, default true
	 * Use this option when calling from a method wich already have got a lock on the tables used
	 * by this method.
	 * @return array with the new lft, rgt and id values, False otherwise
	 */
	function insert_node($lft,$data,$lock = true){
		$root = $this->get_root();
		if($lft > $root[$this->right_col] || $lft < 1)return false;
		$data = $this->sanitize_data($data);
		
		if ($lock)
			$this->lock_tree_table();

		// HCG: I have deprecated the use of the function create_space here - I regard it as good practice NOT
		// to have function calls when a table is locked. NOTE: Under mySQL, you can only lock a table ONCE from
		// a single process.

		$this->EE->db->query('UPDATE '.$this->tree_table.
						' SET '.$this->left_col.' = '.$this->left_col.' + 2 '.
						' WHERE '.$this->left_col.' >= '.$lft);
		$this->EE->db->query('UPDATE '.$this->tree_table.
						' SET '.$this->right_col.' = '.$this->right_col.' + 2 '.
						' WHERE '.$this->right_col.' >= '.$lft);
		
		$data = array_merge($data,array($this->left_col => $lft,$this->right_col => $lft+1));
		$this->EE->db->insert($this->tree_table,$data);
		
		if ($lock)
			$this->unlock_tree_table();
		return array($lft, $lft + 1, $this->EE->db->insert_id());
	}
	
	///////////////////////////////////////
	//  Move functions
	///////////////////////////////////////
	/**
	 * Moves a node with lft to before the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which it will be place before
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_before($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$nlft); //- 1);
	}
	
	/**
	 * Moves a node with lft to after the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which it will be place before
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_after($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$node[$this->right_col] + 1);
	}
	
	/**
	 * Moves a node with lft to be the first child of the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which will be parent
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_append($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$nlft + 1);
	}
	
	/**
	 * Moves a node with lft to be the last of child the node with lft $nlft;
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The lft of the node which will be parent
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node_append_last($lft,$nlft){
		$node = $this->get_node($nlft);
		if(!$node)return false;
		return $this->move_node($lft,$node[$this->right_col]);
	}
	
	/**
	 * Moves a node with lft to nlft.
	 * Primary for internal use.
	 * HCG: We need to look into this again to find better ways of dealing with the gaps.
	 * @since 0.1
	 * @param $lft The lft of the node to be moved
	 * @param $nlft The new lft of the node
	 * @return array with the new lft and rgt values if node moved, false if not
	 */
	function move_node($lft,$nlft){
		// Validate values
		$node = $this->get_node($lft);
		if(!$node || $lft == $nlft)return false;
		$root = $this->get_root();
		if($nlft > $root[$this->right_col] || $nlft < 2 || $lft == 1)return false;
		
		// Lock tree
		$this->lock_tree_table();
		
		// Create WHERE string, so we only affect those we want to
		$where = $this->id_col . ' = '.$node[$this->id_col];
		$descendants = $this->get_descendants($node[$this->left_col],$node[$this->right_col]);
		if($descendants){
			foreach($descendants as $to_move){
				$where .= ' OR '.$this->id_col.' = ' . $to_move[$this->id_col];
			}
		}
		
		// Move the ones to be moved outside the tree
		$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' + '.($root[$this->right_col] - $lft + 1).
			' WHERE '.$where);
		$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' + '.($root[$this->right_col] - $lft + 1).
			' WHERE '.$where);
		
		// Shrink the tree
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		
		$size = ($node[$this->right_col] - $node[$this->left_col] + 1);
		
		if($lft < $nlft){
			// We move the node down in tree (to a greater lft),
			// so compensate for size of the moved
			
			// Create space for nodes
			$this->create_space(($nlft - $size),$size);
			
			// Move them
			$this->EE->db->query(
				'UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.
								(($root[$this->right_col] - $nlft + 1) + $size).
				' WHERE '.$where);
			$this->EE->db->query(
				'UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.
								(($root[$this->right_col] - $nlft + 1) + $size).
				' WHERE '.$where);
			// to get correct rgt value
			$nlft = $lft;
		}
		else{
			// Create space for nodes
			$this->create_space($nlft,$size);
			
			// Move them
			$this->EE->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.($root[$this->right_col] - $nlft + 1).
				' WHERE '.$where);
			$this->EE->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.($root[$this->right_col] - $nlft + 1).
				' WHERE '.$where);
		}
		/*$this->EE->db->select($this->right_col);
		$data = $this->EE->db->get_where($this->tree_table, array($this->left_col => $nlft));
		$ret = $data->row_array();*/
		$ret = array($nlft,$nlft + $node[$this->right_col] - $node[$this->left_col]);
		$this->unlock_tree_table();
		return $ret;
	}
	
	//////////////////////////////////////////////
	//  Delete functions
	//////////////////////////////////////////////
	
	/**
	 * Deletes the node with the lft specified and promotes all children.
	 * @since 0.1
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_node($lft){
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] <= 1)
			return false;
		// Lock table
		$this->lock_tree_table();
		
		$this->EE->db->where($this->id_col,$node[$this->id_col]);
		$this->EE->db->delete($this->tree_table);
		
		// these are not needed, remove_gaps() fixes it
		/*$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' - '.(1).
			' WHERE '.$this->left_col.' > '.$node[$this->left_col]);
		$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' - '.(1).
			' WHERE '.$this->right_col.' > '.$node[$this->right_col]);*/
		
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		$this->unlock_tree_table();
		return true;
	}
	
	/**
	 * Deletes the node with the lft specified and all children.
	 * @since 0.1
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_branch($lft){
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] == 1)
			return false;
		// lock table
		$this->lock_tree_table();
		
		$this->EE->db->where($this->left_col.' BETWEEN '.$node[$this->left_col].' AND '.$node[$this->right_col]);
		$this->EE->db->delete($this->tree_table);
		
		/*$this->EE->db->query('DELETE '.$this->tree_table.
			' WHERE '.$this->left_col.' > '.$node[$this->left_col].
			' AND '.$this->left_col.' < '.$node[$this->right_col]);
*/
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		$this->unlock_tree_table();
		return true;
	}
	
	//////////////////////////////////////////////
	//  Gap functions
	//////////////////////////////////////////////
	
	// HCG: I do not like ANY of these functions because I HATE having function calls involved in scenarios where table locking is done.
	// This is mainly a style choice, but it can bite if one is not VERY careful.
	//
	// NOTE ALSO: I have REMOVED all of the transaction stuff from here, and there is purposefully NO TABLE locking done within these functions
	// The reason is because in mySQL, LOCK TABLES releases any table locks currently held by the thread before acquiring new locks.
	// This implies that if one were to attempt to 'nest' table locks, the second LOCK TABLES instruction will automatically release the first table
	// lock, which is what we definately DO NOT WANT! Again, you can see why I do not like functions where there are locks in place - THEY ARE BAD!
	//
	// All calls to the Gap functions below MUST be called from within a function which has acquired a table lock, otherwise IT WILL BREAK!
	
	/**
	 * Creates an empty space inside the tree beginning at $pos and with size $size.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @param $pos The starting position of the empty space.
	 * @param $size The size of the gap
	 * @return True if success, false if not or if space is outside root
	 */
	function create_space($pos,$size){
		$root = $this->get_root();
		if($pos > $root[$this->right_col] || $pos < $root[$this->left_col])return false;
		$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->left_col.' = '.$this->left_col.' + '.$size.
			' WHERE '.$this->left_col.' >='.$pos);
		$this->EE->db->query('UPDATE '.$this->tree_table.
			' SET '.$this->right_col.' = '.$this->right_col.' + '.$size.
			' WHERE '.$this->right_col.' >='.$pos);
		return true;
	}
	
	/**
	 * Returns the first gap in table.
	 * Primary for internal use.
	 * @since 0.1
	 * @return The starting pos of the gap and size
	 */
	function get_first_gap(){
		$ret = $this->find_gaps();
		return $ret === false ? false : $ret[0];
	}
	
	/**
	 * Removes the first gap in table.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @return True if gap removed, false if none are found
	 */
	function remove_first_gap(){
		$ret = $this->get_first_gap();
		if($ret !== false){
			$this->EE->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->left_col.' = '.$this->left_col.' - '.$ret['size'].
				' WHERE '.$this->left_col.' > '. $ret['start']);
			$this->EE->db->query('UPDATE '.$this->tree_table.
				' SET '.$this->right_col.' = '.$this->right_col.' - '.$ret['size'].
				' WHERE '.$this->right_col.' > '. $ret['start']);
			return true;
		}
		return false;
	}
	/**
	 * Removes all gaps in the table.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @since 0.1
	 * @return True if gaps are found, false if none are found
	 */
	function remove_gaps(){
		$ret = false;
		while($this->remove_first_gap() !== false){$ret = true;}
		return $ret;
	}
	
	/**
	 * Finds all the gaps inside the tree.
	 * Primary for internal use.
	 * HCG: I do not like this!
	 * @since 0.1
	 * @return Returns an array with the start and size of all gaps,
	 * if there are no gaps, false is returned
	 */
	function find_gaps(){
		// Get all lfts and rgts and sort them in a list
		$this->EE->db->select($this->left_col.', '.$this->right_col);
		$this->EE->db->order_by($this->left_col,'asc');
		$table = $this->EE->db->get($this->tree_table);
		$nums = array();
		foreach($table->result() as $row){
			$nums[] = $row->{$this->left_col};
			$nums[] = $row->{$this->right_col};
		}
		sort($nums);
		
		// Init vars for looping
		$old = array();
		$current = 1;
		$foundgap = 0;
		$gaps = array();
		$current = 1;
		$i = 0;
		$max = max($nums);
		while($max >= $current){
			$val = $nums[$i];
			if($val == $current){
				$old[] = $val;
				$foundgap = 0;
				$i++;
			}
			else{
				// have gap or duplicate
				if($val > $current){
					if(!$foundgap)$gaps[] = array('start'=>$current,'size'=>1);
					else{
						$gaps[count($gaps) - 1]['size']++;
					}
					$foundgap = 1;
				}
			}
			$current++;
		}
		return count($gaps) > 0 ? $gaps : false;
	}
	
	//////////////////////////////////////////////
	//  Validate Node/Tree functions
	//////////////////////////////////////////////
	
	/**
	 * Makes a check if the node is a valid node.
	 * @since 0.1
	 * @param $lft The lft of the node
	 * @return true if valid node, else false
	 */
	function is_valid_node($lft){
		$node = $this->get_node($lft);
		if(!$node)return false;
		if($node[$this->left_col]<$node[$this->right_col] &&
			$node[$this->left_col] > 0 &&
			($node[$this->right_col] - $node[$this->left_col]) % 2 == 1)
			return true;
		return false;
	}
	
	/**
	 * Reports any errors in tree.
	 * @since 0.1
	 * @param $ret True if a string with all the errors is requested, default: true
	 * @return A string with the errors if $ret is true,
	 * otherwise, it returns true if there are no errors
	 * and false if there are.
	 */
	function validate($ret = true){
		$this->EE->db->select($this->left_col.', '.$this->right_col);
		$query = $this->EE->db->get($this->tree_table);
		$lftrgt = array();
		$lfts = array();
		$errors = 0;
		$text = '';
		foreach($query->result_array() as $row){
			array_push($lftrgt, $row[$this->left_col]);
			array_push($lfts, $row[$this->left_col]);
			array_push($lftrgt, $row[$this->right_col]);
		}
		sort($lftrgt);
		foreach($lfts as $lft){
			if(!$this->is_valid_node($lft)){
				$text .= 'The node with lft '.$lft.'is not a valid node';
				$errors++;
			}
		}
		$next = 1;
		foreach($lftrgt as $temp){
			if($temp == $next){
				$next++;
			}
			else{
				if($temp > $next){
					$text .= "Gap before $temp\n<br />";
					$next = $temp + 1;
					$errors++;
				}
				else{
					if($temp == ($next - 1)){
						$text .= "Duplicate of $temp<br />";
						$errors++;
					}
				}
			}
		}
		if($errors == 0 && $ret == true){
			$text .= "No errors in Tree found<br />";
		}
		else{
			$text .= "$errors ERRORS found! Correct them as soon as possible!<br />";
		}
		if($ret == true)
			return $text;
		else{
			if($errors == 0)return true;
			return false;
		}
	}
	
	/**
	 * Returns an simple indented html string with the tree structure.
	 * @since 0.1
	 * @param $lft The lft of the parent to display children (default root)
	 * @return Html string
	 */
	function display($lft = 1){
		$node = $this->get_node($lft);
		$str = '';
		$right = array();
		$query = $this->EE->db->query('SELECT '.$this->title_col.', '.$this->left_col.', '.
									$this->right_col.' FROM '.$this->tree_table.
			' WHERE '.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' ORDER BY '.$this->left_col.' ASC');
		foreach($query->result_array() as $row){
			if(count($right) > 0){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			$str .= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',count($right)) . $row[$this->title_col] . "<br />\n";
			$right[] = $row[$this->right_col];
		}
		return $str;
	}
	
	//////////////////////////////////////////////
	//  Helper functions
	//////////////////////////////////////////////
	
	/**
	 * Sanitizes the data given.
	 * Removes the left_col and right_col from the data, if they exists in $data.
	 * @since 0.1.2
	 * @param $data The data to be sanitized
	 * @return The sanitized data
	 */
	function sanitize_data($data){
		// Remove fields which potentially can damage the tree structure
		if(is_array($data)){
			unset($data[$this->left_col]);
			unset($data[$this->right_col]);
		}
		elseif(is_object($data)){
			unset($data->{$this->left_col});
			unset($data->{$this->right_col});
		}
		return $data;
	}
	
	/**
	 * Logs a debug message if debug is on.
	 * $message is taken by reference to avoid copying to much.
	 * @since 0.1.6-fix
	 * @param $message The message to be logged
	 */
	function debug_message(&$message){
		if($this->debug_on == true){
			// save debug message
			log_message('debug','MPTtree: '.$message);
		}
	}
	
	/**
	 * Changes the debug mode.
	 * Default is off.
	 * @since 0.1.6-fix
	 * @param $value True if debug shall be on, false if to turn it off.
	 */
	function set_debug($value){
		$this->debug_on = $value;
	}
	
	
	
	
	
	
	
	//////////////////////////////////////////////
	//  Iain's Functions
	//////////////////////////////////////////////
	
	
	/**
	 * Get a flat array of nodes
	 *
	 * @param 	integer 	$root		The lft of the node to be root in the query, default: 1
	 * @return 	mixed		integer 	integer Level of the Node (0 = Root) or boolean False
	 */
	 function get_flat_tree($root = 1){
		$node = $this->get_node($root);
		if($node == false)
			return false;
		// query
		$query = $this->EE->db->query('SELECT * FROM '.$this->tree_table.' 

			LEFT JOIN exp_channel_titles
			ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
			 WHERE '.$this->tree_table.'.'.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' GROUP BY '.$this->tree_table.'.node_id  
			ORDER BY '.$this->tree_table.'.'.$this->left_col.' ASC');
		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$level = 0;
		$i=0;
		foreach($query->result_array() as $row){

			// go more shallow, if needed
			if(count($right)){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $level){
				end($current);
			}
			// the stack contains all parents, current and maybe next level
			// $current =& $stack[count($right)];
			// add the data
			$current[] = $row;
			// go one level deeper with the index
			$level = count($right);
			$right[] = $row[$this->right_col];

			$current[$i]['level'] = $level;
			$current[$i]['childs'] = round(($row[$this->right_col] - $row[$this->left_col]) / 2, 0);
			$i++;
		}
		
		return $result;
		
	}
	
	function get_flat_tree_v2($root = 1){
		$node = $this->get_node($root);
		if($node == false)
			return false;
		// query
		$query = $this->EE->db->query('SELECT * FROM '.$this->tree_table.' 
			LEFT JOIN exp_channel_titles
				ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
			 WHERE '.$this->tree_table.'.'.$this->left_col.' BETWEEN '.$node[$this->left_col].
			' AND '.$node[$this->right_col].
			' GROUP BY '.$this->tree_table.'.node_id  
			ORDER BY '.$this->tree_table.'.'.$this->left_col.' ASC');
		$right = array();
		$result = array();
		$current =& $result;
		$stack = array();
		$stack[0] =& $result;
		$level = 0;
		$i=0;
		foreach($query->result_array() as $row){
		
		// print_r($row);
			
			// go more shallow, if needed
			if(count($right)){
				while($right[count($right)-1] < $row[$this->right_col]){
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $level){
				end($current);
			}
			// the stack contains all parents, current and maybe next level
			// $current =& $stack[count($right)];
			// add the data
			$current[] = $row;
			// go one level deeper with the index
			$level = count($right);
			$right[] = $row[$this->right_col];

			$current[$i]['level'] = $level;
			$current[$i]['childs'] = round(($row[$this->right_col] - $row[$this->left_col]) / 2, 0);
			$i++;
		}
		
		return $result;
		
	}

	
	// return how many nodes are at a level, pass get_flat_tree
	// get_instances_of_level($get_flat_tree, 2); - how many nodes at level 2
	function get_instances_of_level($array, $level)
	{
		$data = 0;
		foreach($array as $v)
		{
		    if($v['level'] == $level)
		    $data++;
		}
		return $data;
	}
	
	
	/**
	 * Get the Id of a Node depending on its left or right Value
	 *
	 * @param 	integer 	$directionValue		Value of left or right Border
	 * @param 	string 		$direction			left or right Border ("l" for left, "r" for right)
	 * @return 	mixed							integer Id of the Node or boolean False
	 */
	function get_id ($direction_value, $direction) {
		$sql = 'SELECT `node_id` 
				FROM `'.$this->tree_table.'` 
				WHERE `'.$direction.'` = '.$direction_value.'';
		
		$results = $this->EE->db->query ($sql);
		
		// print_r($results);
		
		foreach($results->result_array() as $row)
		{
			return $row['node_id'];
		}
		
	}
	
	
	/**
	 * Get the Level of a Node
	 *
	 * @param 	integer 	$node_id		Id of the Node
	 * @return 	mixed		integer 	integer Level of the Node (0 = Root) or boolean False
	 */
	function get_node_level($node_id) {
		
		
		$sql = 'SELECT COUNT(*) AS `level` 
				FROM `'.$this->tree_table.'` `p`,`'.$this->tree_table.'` `n` 
				WHERE `n`.`'.$this->left_col.'` 
				BETWEEN `p`.`'.$this->left_col.'` 
				AND `p`.`'.$this->right_col.'`
				GROUP BY `n`.`'.$this->left_col.'` 
				ORDER BY ABS(`n`.`node_id` - '.$node_id.')';
		
		$results = $this->EE->db->query ($sql);
		
		// print_r($results);
		
		foreach($results->result_array() as $row)
		{
			return $row['level'];
		}
		
		
	}
	
	
	
	
	
	/**
	 * Move a Node/Branch LEFT
	 *
	 * @param 	integer 	$node_id 	Id of the Node
	 * @return 	boolean					True or False
	 * 
	 * Swap space with the left Brother
	 */
	public function move_left ($id) 
	{
			
		$node_level = $this->get_node_level($id);
		
		if ($node_level == 1) {
			return false;
		}
		$a = $this->get_node_byid($id);
				
		$a_lft = $a['lft'];
		$a_rgt = $a['rgt'];
		
		if (!$b_id = $this->get_id($a_lft - 1,'rgt')) {
			// can't move
			return false;
		}
		if (!$b = $this->get_node_byid ($b_id)) {
			// can't move
			return false;
		}
		
		$b_id = $this->get_id($a_lft - 1,'rgt');
		$b = $this->get_node_byid($b_id);

		$b_lft = $b['lft'];
		$b_rgt = $b['rgt'];

        $diffRgt = $a_rgt - $b_rgt;
        $diffLft = $a_lft - $b_lft;
        
		$sql1 = sprintf ('UPDATE `%1$s` SET `%2$s` = %11$d WHERE `%2$s` <> %11$d',
			$this->tree_table,'moved','rgt','lft',(int)$diffRgt,(int)$diffLft,(int)$a_lft,(int)$a_rgt,(int)$b_lft,(int)$b_rgt,0,1);
			
		$sql2 = sprintf ('UPDATE `%1$s` SET `%3$s` = `%3$s` + %5$d,`%4$s` = `%4$s` + %5$d,`%2$s` = %12$d WHERE `%4$s` BETWEEN %9$d AND %10$d',
			$this->tree_table,'moved','rgt','lft',(int)$diffRgt,(int)$diffLft,(int)$a_lft,(int)$a_rgt,(int)$b_lft,(int)$b_rgt,0,1);
			
		$sql3 = sprintf ('UPDATE `%1$s` SET `%3$s` = `%3$s` - %6$d,`%4$s` = `%4$s` - %6$d WHERE `%4$s` BETWEEN %7$d AND %8$d AND `%2$s` = %11$d',
			$this->tree_table,'moved','rgt','lft',(int)$diffRgt,(int)$diffLft,(int)$a_lft,(int)$a_rgt,(int)$b_lft,(int)$b_rgt,0,1);
			
			
		$sql4 = sprintf ('UPDATE `%1$s` SET`%2$s` = %11$d WHERE `%2$s` <> %11$d',
			$this->tree_table,'moved','rgt','lft',(int)$diffRgt,(int)$diffLft,(int)$a_lft,(int)$a_rgt,(int)$b_lft,(int)$b_rgt,0,1);
	
        $this->lock_tree_table ();
        
        $this->EE->db->query($sql1);
        $this->EE->db->query($sql2);
        $this->EE->db->query($sql3);
        $this->EE->db->query($sql4);
        
        $this->unlock_tree_table ();
        
        return true;
	}
	
	/**
	 * Move a Node/Branch RIGHT
	 *
	 * @param 	integer 	$nodeId 	Id of the Node
	 * @return 	boolean					True or False
	 */
	public function move_right($id) {
		
		$node_level = $this->get_node_level($id);
		
		if ($node_level == 1) {		
			return false;
		}
		
		$a = $this->get_node_byid($id);
		$a_lft = $a['lft'];
		$a_rgt = $a['rgt'];
		
		if (!$b_id = $this->get_id($a_rgt + 1,'lft')) {
			return false;
		}
		
		if (!$b = $this->get_node_byid($b_id)) {	
			return false;
		}
		
		$b_lft = $b['lft'];
		$b_rgt = $b['rgt'];
		
        $diffRgt = $b_rgt - $a_rgt;
        $diffLft = $b_lft - $a_lft;
        
        
		$sql1 = sprintf ('UPDATE `%1$s` SET `%2$s` = %11$d WHERE `%2$s` <> %11$d',
		        $this->tree_table,'moved','lft','rgt',(int)$diffLft,(int)$diffRgt,(int)$b_lft,(int)$b_rgt,(int)$a_lft,(int)$a_rgt,0,1);
		        
		$sql2 = sprintf ('UPDATE `%1$s` SET `%4$s` = `%4$s` - %5$d, `%3$s` = `%3$s` - %5$d, `%2$s` = %12$d WHERE `%3$s` BETWEEN %7$d AND %8$d',
		        $this->tree_table,'moved','lft','rgt',(int)$diffLft,(int)$diffRgt,(int)$b_lft,(int)$b_rgt,(int)$a_lft,(int)$a_rgt,0,1);
		        
		$sql3 = sprintf ('UPDATE `%1$s` SET `%4$s` = `%4$s` + %6$d, `%3$s` = `%3$s` + %6$d WHERE `%3$s` BETWEEN %9$d AND %10$d AND `%2$s` = %11$d',
		        $this->tree_table,'moved','lft','rgt',(int)$diffLft,(int)$diffRgt,(int)$b_lft,(int)$b_rgt,(int)$a_lft,(int)$a_rgt,0,1);
		        
		$sql4 = sprintf ('UPDATE `%1$s` SET `%2$s` = %11$d WHERE `%2$s` <> %11$d',
		        $this->tree_table,'moved','lft','rgt',(int)$diffLft,(int)$diffRgt,(int)$b_lft,(int)$b_rgt,(int)$a_lft,(int)$a_rgt,0,1);

		$this->lock_tree_table();
		
	    $this->EE->db->query($sql1);
        $this->EE->db->query($sql2);
        $this->EE->db->query($sql3);
        $this->EE->db->query($sql4);
        
        $this->unlock_tree_table();
        
        return true;
	}
	
	
	/**
	 * Move a Node/Branch UP
	 *
	 * @param 	integer 	$id 	Id of the Node
	 * @return	boolean					True or False
	 */
	public function move_up($id) {
		$node_level = $this->get_node_level($id);
		
		if ($node_level == 1) {			
			return false;
		}

		if ($node_level == 2) {
			return false;
		}
		
        do {
            if (!$moved = $this->move_right($id)) {
            	break;
            }
        } while (true === $moved);
        
		$a = $this->get_node_byid($id);
		$a_lft = $a['lft'];
		$a_rgt = $a['rgt'];
		
		if (!$b_id = $this->get_id($a_rgt + 1,'rgt')) {			
			return false;
		}
		
		if (!$b = $this->get_node_byid($b_id)) {
			return false;
		}
		
		$b_lft = $b['lft'];
		$b_rgt = $b['rgt'];
		
		$node_width = $a_rgt - $a_lft + 1;
		
		$sql1 = sprintf ('UPDATE `%1$s` SET `%2$s` = `%2$s` + %9$d,`%3$s` = `%3$s` + %9$d WHERE `%3$s` BETWEEN %5$d AND %6$d',
		        $this->tree_table,'rgt','lft','node_id',(int)$a_lft,(int)$a_rgt,(int)$node_width,(int)$b_id,1);
		$sql2 = sprintf ('UPDATE `%1$s` SET `%2$s` = `%2$s` - %7$d WHERE `%4$s` = %8$d',
		        $this->tree_table,'rgt','lft','node_id',(int)$a_lft,(int)$a_rgt,(int)$node_width,(int)$b_id,1);       
		
		$this->lock_tree_table();
		
	    $this->EE->db->query($sql1);
        $this->EE->db->query($sql2);
        
        $this->unlock_tree_table();
        
        return true;
	}
	
	
	
	
	
	/**
	 * Move a Node/Branch DOWN
	 *
	 * @param 	integer 	$id 		Id of the Node
	 * @return 	boolean					True or False
	 */
	public function move_down($id) {
		$node_level = $this->get_node_level($id);
		
		if ($node_level == 1) {
			return false;
		}
		
		$a = $this->get_node_byid($id);
		$a_lft = $a['lft'];
		$a_rgt = $a['rgt'];
		
		if (!$b_id = $this->get_id($a_lft - 1,'rgt')) {
			return false;
		}
		if (!$b = $this->get_node_byid($b_id)) {
			return false;
		}

		$b_lft = $b['lft'];
		$b_rgt = $b['rgt'];
		
		$node_width = $a_rgt - $a_lft + 1;
		
		$sql1 = sprintf ('UPDATE `%1$s` SET `%2$s` = `%2$s` - %9$d, `%3$s` = `%3$s` - %9$d WHERE `%3$s` BETWEEN %5$d AND %6$d',
		        $this->tree_table,'rgt','lft','node_id',(int)$a_lft,(int)$a_rgt,(int)$node_width,(int)$b_id,1);
		        
		$sql2 = sprintf ('UPDATE `%1$s` SET `%2$s` = `%2$s` + %7$d WHERE `%4$s` = %8$d',
		        $this->tree_table,'rgt','lft','node_id',(int)$a_lft,(int)$a_rgt,(int)$node_width,(int)$b_id,1);       
		
		$this->lock_tree_table();
		
	    $this->EE->db->query($sql1);
        $this->EE->db->query($sql2);
        
        $this->unlock_tree_table();
        
        return true;
	}


	
	// build unordered list from nested array
	function mptree_cp($array)
	{
		
    	$str = '<ul class="treelist">';
    	foreach($array as $data)
    	{
    		
    		$template_group = 	'/'.$data['group_name']; 
    		$template_name = 	'/'.$data['template_name']; 
    		$url_title = 		'/'.$data['url_title'];
    		
    		// don't display /index
    		if($template_name == '/index')
    		{
    			$template_name = '';
    		}
    		
    		$node_url = 	$this->EE->functions->fetch_site_index().$template_group.$template_name.$url_title;
    		$viewed_url = 	$this->EE->functions->fetch_site_index().'/'.$this->EE->uri->uri_string();
    		
    		// override template and entry slug with custom url if set
    		if($data['custom_url'] != '')
    		{
    			$node_url = $data['custom_url'];
    		}
    		
			$active = '';
    		if($node_url == $viewed_url)
    		{
    			$active = ' class="active"';
    		}
    		
        	$str .= '<li'.$active.'>';
        	$str .= '<a href="'.$node_url.'"'.$active.'>'.$data['label'].'</a>'; 
        	if(isset($data['children'])){
	            $str .= $this->EE->mpttree->mptree_cp($data['children']);
	        }
	        
        $str .= '</li>';
        }    
        $str .= '</ul>';
   	 	return $str;
    }
    
    
    /**
	 * Converts the tree structure in the tree to an array.
	 * Array Example:
	 * @code
	 * Array([0] => Array([id] => 1,
	 *                    [lft] => 1,
	 *                    [rgt] => 4,
	 *                    [children] => Array(
	 *                        [0] => Array([id] => 2,
	 *                                     [lft] => 2,
	 *                                     [rgt] => 3
	 *                                    )
	 *                    )
	 *      )
	 * )
	 * @endcode
	 * @param $root The node lft value that shall be root in the tree (local scope)
	 * @param $root_entry_id The node that shall be root in the tree that contains the EE entry_id
	 * @param $root_node_id The node that shall be root in the tree that contains the node_id
	 * @return A recursive array, false if the root node was not found
	 */
		function tree2array_v2($root = 1, $root_entry_id = NULL, $root_node_id = NULL, $status_sql = '')
		{
			
			if($root_entry_id)
			{
				$node = $this->get_node_by_entry_id($root_entry_id);
			}
			elseif($root_node_id)
			{
				$node = $this->get_node_by_nodeid($root_node_id);
			}
			else
			{
				$node = $this->get_node($root);
			}
			if($node == false)
				return false;
				
			// query
			$query = 'SELECT 
				'.$this->tree_table.'.node_id,
				'.$this->tree_table.'.lft,
				'.$this->tree_table.'.rgt,
				'.$this->tree_table.'.label, 
				'.$this->tree_table.'.entry_id, 
				'.$this->tree_table.'.template_path, 
				'.$this->tree_table.'.custom_url, 
				'.$this->tree_table.'.extra, ';
				
			if($this->EE->input->get('method') == 'edit_nodes')
			{
				$query .=  'exp_statuses.status,
							exp_statuses.highlight,';
			}
				
			$query .=  'exp_channel_titles.entry_id, 
				exp_channel_titles.channel_id, 
				exp_channel_titles.title, 
				exp_channel_titles.url_title, 
				exp_channel_titles.status, 
				exp_channel_titles.entry_date, 
				
				exp_templates.template_id, 
				exp_templates.group_id, 
				exp_templates.template_name, 
				exp_template_groups.group_id, 
				exp_template_groups.group_name,
				exp_template_groups.is_site_default
				
				FROM '.$this->tree_table.' 	
					LEFT JOIN exp_channel_titles
						ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
						
						LEFT JOIN exp_templates
						ON ('.$this->tree_table.'.template_path=exp_templates.template_id)
						
						LEFT JOIN exp_template_groups
						ON (exp_template_groups.group_id=exp_templates.group_id)';
			
			// are we in the cp? lets get status colors
			if($this->EE->input->get('method') == 'edit_nodes')
			{
				$query .=  'LEFT JOIN exp_statuses
							ON (exp_statuses.status=exp_channel_titles.status)';
			}

			$query .=	' WHERE ('.$this->left_col.
						' BETWEEN '.$node[$this->left_col].
						' AND '.$node[$this->right_col].')';
			
			// add an extra sql statement for statuses,
			// nodes not attached to entries will have a NULL status
			
			
			if($status_sql && $status_sql != 'ALL')
			{
				$query .= ' AND (exp_channel_titles.status IN ('.$status_sql.') OR exp_channel_titles.status IS NULL) ';
			}

			$query .=	' GROUP BY '.$this->left_col.
						' ORDER BY '.$this->left_col.' ASC';
						
			$query = $this->EE->db->query($query);				
			$right = array();
			$result = array();
			$current =& $result;
			$stack = array();
			$stack[0] =& $result;
			$lastlevel = 0;
			$level = 1;
			foreach($query->result_array() as $row){
			
			$level = count($right);
			$row['level'] = $level;
			
				// go more shallow, if needed
				if(count($right) > 1){
					
					while($right[count($right)-1] < $row[$this->right_col])
					{
						$level = $level-1;
						array_pop($right);
						$row['level'] = $level;
					}
				}

				// Go one level deeper?
				if(count($right) > $lastlevel){
					
					end($current);
					
					$current[key($current)]['has_children'] = 'yes';
					
					$current[key($current)]['children'] = array();
					
					$stack[count($right)] =& $current[key($current)]['children'];
					$row['level'] = $level;
				}
		
				// the stack contains all parents, current and maybe next level
				$current =& $stack[count($right)];
				// add the data
				$current[] = $row;
				// go one level deeper with the index
				
				$lastlevel = count($right);
				$right[] = $row[$this->right_col];
			}
			
			return $result;
		}
	
	
	
	// build unordered list from nested array
	// main interface list for drag/drop
	// recursively loop over nested array of nodes
	function mptree_cp_list($array, $ind = '')
	{
		
		if($array[0]['level'] == 0)
		{
			$str = "<h3>";
		}
		elseif($array[0]['level'] == 1)
		{
			$str = "<ol id='taxonomy-list'>\n";
		}
		else
		{
			$str = "<ol>";
		}
		
		
		// $str = ($array[0]['level'] == 0) ? "<ul id='taxonomy-list'>\n" : $ind."	<ul>\n";
    	
    	foreach($array as $data)
    	{
    		$node_id			= $data['node_id'];
    		$tree_id			= $this->EE->input->get('tree_id');
    		
    		// if the template group is the site default, hide it.
    		$template_group 	= ($data['is_site_default'] !='y') ? '/'.$data['group_name'] : ''; 
    		
    		// hide /index templates from the urls
    		$template_name		= ($data['template_name'] != 'index') ? '/'.$data['template_name'] : '';
    		
    		$url_title 			= '/'.$data['url_title'];
    		$custom_url			= (isset($data['custom_url'])) ? $data['custom_url'] : '';
    		$children 			= (isset($data['children'])) ? $data['children'] : '';
    		$label				= $data['label'];
    		$highlight			= $data['highlight'];
    		$status				= ($data['status'] && $data['status'] != 'open') ? '<em class="status_indicator" title="Status: '.ucfirst($data['status']).'" style="background-color: #'.$highlight.'">['.ucfirst($data['status']).']</em>' : '';
    		$site_url			= $this->EE->functions->fetch_site_index();
    		$level 				= $data['level'];
    		
    		// indentation
    		$ind				= str_repeat('	', $data['level']+1);

    		// build the edit entry link
    		$edit_entry_link	= BASE.'&amp;C=content_publish&amp;M=entry_form&amp;channel_id='.$data['channel_id'].'&amp;entry_id='.$data['entry_id'];
    		
    		// build the delete node/branch link (if its a branch, we delete all children too)
    		$delete_branch 	= "$this->base&amp;method=delete_branch&amp;node_id=$node_id&amp;tree_id=$tree_id";
    		$delete_node 	= "$this->base&amp;method=delete_node&amp;node_id=$node_id&amp;tree_id=$tree_id";
    		$delete_link	= (isset($data['children'])) ? $delete_branch : $delete_node;
    		$delete_class	= (isset($data['children'])) ? 'branch' : 'node';
    		
    		if($custom_url)
    		{
    			$node_url = $custom_url;
    			// if we've got a page_uri set, go fetch the pages uri
    			if($node_url == "[page_uri]")
    			{
    				$site_id = $this->EE->config->item('site_id');
    				$node_url = $this->entry_id_to_page_uri($data['entry_id'], $site_id);
    			}
    			// does the custom url start with http://, 
    			// if not we add our site_index as it'll be a relative link
    			$node_url = ((substr(ltrim($node_url), 0, 7) != 'http://') && (substr(ltrim($node_url), 0, 8) != 'https://') ? $this->EE->functions->fetch_site_index() : '') . $node_url;
    		}
    		else
    		{
    			$node_url = ($custom_url =='') ? $site_url.$template_group.$template_name.$url_title : $custom_url;
    		}
		
			// remove double slashes
			$node_url = preg_replace("#(^|[^:])//+#", "\\1/", $node_url);
			// remove trailing slash
			$node_url = rtrim($node_url,"/");
			
			if($array[0]['level'] == 0)
			{
				$str .= "";
			}
			else
			{
				$str .= $ind."<li id='list_$node_id'>\n";
			}
			
			$str .= $ind."	<div class='item-wrapper'><div class='item-handle'></div>\n";
        	$str .= $ind."		$status <a href='$this->base&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id'>$label</a>\n"; 
        	$str .= $ind."		<div class='item-options'> \n";
			if($data['entry_id'])
			{
				$str .= $ind."  		<a href='$edit_entry_link'>Edit Entry</a> \n";
			}
			$str .= $ind."  		<a href='$node_url' target='_blank' title='Visit: $node_url'>Visit Page</a> \n";
			$str .= $ind."  		<a href='$this->base&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id'>Edit Node</a> \n";
			$str .= $ind."  		<a href='$delete_link' class='delete_$delete_class'>x</a> \n";
			$str .= $ind."		</div> \n";
        	$str .= $ind."	</div> \n\n";
        	
        	if($array[0]['level'] == 0)
			{
				$str .= "</h3>";
			}
        	
        	if($children){
        		// curse, damnit recurse!
	            $str .= $this->EE->mpttree->mptree_cp_list($children, $ind);
	        }

        	if($array[0]['level'] == 0)
			{
				$str .= "";
			}
			else
			{
				$str .= "</li>";
			}
        }    

        if($array[0]['level'] == 0)
		{
			$str .= "";
		}
		else
		{
			$str .= "</ol>";
		}
 
   	 	return $str;
    }
	
	

	
	function get_node_by_entry_id($entry_id){
		if ( ! isset($this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['entry_'.$entry_id]))
		{
			$query = $this->EE->db->get_where($this->tree_table,array('entry_id' => $entry_id),1);
			$this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['entry_'.$entry_id] = ($query->num_rows()) ? $query->row_array() : false;
		}
		return $this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['entry_'.$entry_id];
	}
	
	function get_node_by_nodeid($node_id){
		if ( ! isset($this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['node_'.$node_id]))
		{
			$query = $this->EE->db->get_where($this->tree_table,array('node_id' => $node_id),1);
			$this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['node_'.$node_id] = ($query->num_rows()) ? $query->row_array() : false;
		}
		return $this->EE->session->cache['taxonomy']['tree_'.$this->tree_table]['node_'.$node_id];
	}
	
	function get_node_by_url_title($url_title){
		$query = $this->EE->db->query('SELECT  
		
		
					'.$this->tree_table.'.node_id,
					'.$this->tree_table.'.lft,
					'.$this->tree_table.'.rgt,
					'.$this->tree_table.'.label,
					'.$this->tree_table.'.entry_id,
					'.$this->tree_table.'.template_path,
					'.$this->tree_table.'.custom_url,
					'.$this->tree_table.'.extra,
					exp_channel_titles.site_id,
					exp_channel_titles.channel_id,
					exp_channel_titles.title,
					exp_channel_titles.url_title,
					exp_channel_titles.status
					
					
					FROM '.$this->tree_table.
		
				' 	LEFT JOIN exp_channel_titles
					ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
					
				 WHERE exp_channel_titles.url_title = "'.$url_title.'"');
		$return = $query->num_rows() ? $query->row_array() : false;
		return $return;
	}
	
function get_parents_crumbs($lft,$rgt){
	
		if ( ! isset($this->EE->session->cache['taxonomy']['crumbs_array'][$lft.'|'.$rgt]))
		{
			// need to optimise this query
			$query = $this->EE->db->query('SELECT 
			
					'.$this->tree_table.'.node_id,
					'.$this->tree_table.'.lft,
					'.$this->tree_table.'.rgt,
					'.$this->tree_table.'.label, 
					'.$this->tree_table.'.entry_id, 
					'.$this->tree_table.'.template_path, 
					'.$this->tree_table.'.custom_url, 
					'.$this->tree_table.'.extra, 
					
					exp_channel_titles.entry_id, 
					exp_channel_titles.channel_id, 
					exp_channel_titles.title, 
					exp_channel_titles.url_title, 
					exp_channel_titles.status, 
					exp_channel_titles.entry_date, 
					
					exp_templates.template_id, 
					exp_templates.group_id, 
					exp_templates.template_name, 
					exp_template_groups.group_id, 
					exp_template_groups.group_name,
					exp_template_groups.is_site_default
					
					FROM '.$this->tree_table.
			
					' 	LEFT JOIN exp_channel_titles
						ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
						
						LEFT JOIN exp_templates
						ON ('.$this->tree_table.'.template_path=exp_templates.template_id)
						
						LEFT JOIN exp_template_groups
						ON (exp_template_groups.group_id=exp_templates.group_id)
						
					 WHERE '.$this->left_col.' < '.$lft.
					' AND '.$this->right_col.' > '.$rgt.
					' GROUP BY '.$this->left_col. 
					' ORDER BY '.$this->left_col.' ASC');
					
					 //print_r($query->result_array());
			$this->EE->session->cache['taxonomy']['crumbs_array'][$lft.'|'.$rgt] = ($query->num_rows()) ? $query->result_array() : array();
		}
		
		return $this->EE->session->cache['taxonomy']['crumbs_array'][$lft.'|'.$rgt];
	
	}
    
    
    /**
	 * Returns immediate children of the node with the values lft and rgt.
	 * @param $lft The lft value of node
	 * @param $rgt The rgt value of node
	 * @return A multidimensional accociative array with the table rows,
	 * but if no rows returned, empty array
	 */
	function get_children_ids($node_id){
		$result = $this->EE->db->query(
		"SELECT node.*, (COUNT(parent.node_id) - (sub_tree.depth + 1)) AS depth
		FROM ".$this->tree_table." AS node,
		    ".$this->tree_table." AS parent,
		    ".$this->tree_table." AS sub_parent,
		    (
		        SELECT node.node_id, (COUNT(parent.node_id) - 1) AS depth
		        FROM ".$this->tree_table." AS node,
		        ".$this->tree_table." AS parent
		        WHERE node.".$this->left_col." BETWEEN parent.".$this->left_col." AND parent.".$this->right_col."
		        AND node.node_id = ".$node_id."
		        GROUP BY node.node_id
		        ORDER BY node.".$this->left_col."
		    )AS sub_tree
		WHERE node.lft BETWEEN parent.".$this->left_col." AND parent.".$this->right_col."
		    AND node.".$this->left_col." BETWEEN sub_parent.".$this->left_col." AND sub_parent.".$this->right_col."
		    AND sub_parent.node_id = sub_tree.node_id
		GROUP BY node.node_id
		HAVING depth = 1
		ORDER BY node.".$this->left_col.";");
		return $result->num_rows() ? $result->result_array() : array();
	}
	
	function build_session_path_array(){
	
		// need to optimise this query
		$query = $this->EE->db->query('SELECT 
		
				'.$this->tree_table.'.node_id,
				'.$this->tree_table.'.lft,
				'.$this->tree_table.'.rgt,
				'.$this->tree_table.'.label, 
				'.$this->tree_table.'.entry_id, 
				'.$this->tree_table.'.template_path, 
				'.$this->tree_table.'.custom_url, 
				'.$this->tree_table.'.extra, 
				
				exp_channel_titles.entry_id, 
				exp_channel_titles.channel_id, 
				exp_channel_titles.title, 
				exp_channel_titles.url_title, 
				exp_channel_titles.status, 
				exp_channel_titles.entry_date, 
				
				exp_templates.template_id, 
				exp_templates.group_id, 
				exp_templates.template_name, 
				exp_template_groups.group_id, 
				exp_template_groups.group_name,
				exp_template_groups.is_site_default
				
				FROM '.$this->tree_table.'
		
				 	LEFT JOIN exp_channel_titles
					ON ('.$this->tree_table.'.entry_id=exp_channel_titles.entry_id)
					
					LEFT JOIN exp_templates
					ON ('.$this->tree_table.'.template_path=exp_templates.template_id)
					
					LEFT JOIN exp_template_groups
					ON (exp_template_groups.group_id=exp_templates.group_id)
				
					GROUP BY '.$this->left_col.' 
				 	ORDER BY '.$this->left_col.' ASC');
				
				 //print_r($query->result_array());
		return $query->num_rows() ? $query->result_array() : array();
	}
	
	
	
	 /**
	 * Returns all nodes from a tag pair and produces a nested list
	 * @param $array The nested array of nodes
	 * @param $tagdata The data between the tag
	 * @param $options An array of various user defined options
	 * @return A nested <ul> for building a navigation from nodes
	 */
	function build_list($array, $tagdata, $options)
	{
		$options['depth'] 			= ($options['depth']) ? $options['depth'] : 100 ;
		$options['display_root'] 	= ($options['display_root']) ? $options['display_root'] : "yes";
		$options['path'] 			= ($options['path']) ? $options['path'] : NULL;
		$options['entry_id'] 		= ($options['entry_id'] ) ? $options['entry_id'] : NULL;
		$options['ul_css_id'] 		= ($options['ul_css_id'] ) ? $options['ul_css_id'] : NULL;
		$options['ul_css_class'] 	= ($options['ul_css_class'] ) ? $options['ul_css_class'] : NULL;
		$options['hide_dt_group'] 	= ($options['hide_dt_group'] ) ? $options['hide_dt_group'] : NULL;
		$options['auto_expand'] 	= ($options['auto_expand']) ? $options['auto_expand'] : NULL;
		$options['node_active_class'] = ($options['node_active_class']) ? $options['node_active_class'] : 'active';
				
		$str = '';
		$ul_id = '';
		$ul_class = '';
		
		if(!$array)
      		return false;
		
		// apply the css id to th outermost <ul>
		if($options['ul_css_id'])
		{
			$ul_id = ' id="'.$options['ul_css_id'].'"';			
		}
		
		// apply the css class to th outermost <ul>
		if($options['ul_css_class'])
		{
			$ul_class = ' class="'.$options['ul_css_class'].'"';
		}
		
    	$opening_ul = "<ul".$ul_id.$ul_class.">\n";		
		$closing_ul = "</ul>\n";
		
		// Added by @nevsie
		$level_count = 0;
        $level_total_count = count($array);
		
    	foreach($array as $data)
    	{    
    	
	    	$active_parent = '';
	    	$level_count ++;
	    	
	    	// flag active parents
	    	if($options['path'])
			{
				foreach($options['path'] as $parent_node)
				{
					if($data['node_id'] === $parent_node['node_id'])
					{
						$active_parent = 'active_parent';
						// Added by @nevsie
						// Grab the active levels Left and Right Value, and nest in relation to its level
						$this->actp_lev[$data['level']]['act_lft']	= $data['lft'];
						$this->actp_lev[$data['level']]['act_rgt']	= $data['rgt'];
					}
				}
			
			}
			
			$active = '';
			if($data['entry_id'] == $options['entry_id'] && $data['entry_id'] != '' && $options['entry_id'] != '')
			{
				$active = $options['node_active_class'];
				// Added by @nevsie
				$this->act_lev[$data['level']]['act_lft']	= $data['lft'];
				$this->act_lev[$data['level']]['act_rgt']	= $data['rgt'];
			}
    		    		
    		if(($data['level'] == 0) && ($options['display_root'] =="no" && isset($data['children'])))
    		{
    		 $str = $this->EE->mpttree->build_list($data['children'], $tagdata, $options);
    		 $opening_ul = '';
    		 $closing_ul = '';
    		}
    		
    		else
    		{
    			// move onwards only if autoexpand is no, or level = 0, or we're on a sibling of an active branch/node using autoexpand=yes
				if (	$options['auto_expand'] == 'no'
						||
						$data['level'] == 0
						||
						(
							( // are we on a sibling of an active parent?
							isset($this->actp_lev[($data['level']-1)]['act_lft'])
							&& 
							$data['lft'] >= $this->actp_lev[($data['level']-1)]['act_lft']
							&& 
							$data['rgt'] <= $this->actp_lev[($data['level']-1)]['act_rgt']
							)
						||
							( // are we on a sibling of the active
							isset($this->act_lev[($data['level']-1)]['act_lft'])
							&& 
							$data['lft'] >= $this->act_lev[($data['level']-1)]['act_lft']
							&& 
							$data['rgt'] <= $this->act_lev[($data['level']-1)]['act_rgt']
							)
						)
					)
				{
					
					// remove default template group segments
					$template_group = ($data['is_site_default'] == 'y') ? '' : '/'.$data['group_name'];
					$template_name = 	'/'.$data['template_name']; 
					$url_title = 		'/'.$data['url_title'];
					
					// don't display /index
					if($template_name == '/index')
					{
						$template_name = '';
					}
	
					$node_url = 	$this->EE->functions->fetch_site_index().$template_group.$template_name.$url_title;
					
					$viewed_url = 	$this->EE->functions->fetch_site_index().'/'.$this->EE->uri->uri_string();
	
					// override template and entry slug with custom url if set
					if($data['custom_url'])
					{
						
						$node_url = $data['custom_url'];
						
						// if we've got a page_uri set, go fetch the pages uri
						if($node_url == "[page_uri]")
						{
							$site_id = $this->EE->config->item('site_id');
							$node_url = $this->EE->functions->fetch_site_index().$this->entry_id_to_page_uri($data['entry_id'], $site_id);
						}
						elseif($node_url[0] == "#")
						{
							$node_url = $data['custom_url'];
						}
						// if it's a relative url, prepend the site index
						// otherwise just roll with the user's input
						else
						{
							// does the custom url start with http://, 
							// if not we add our site_index as it'll be a relative link
							// and the nav tag will apply the $active css class to the node
						$node_url = ((substr(ltrim($node_url), 0, 7) != 'http://') && (substr(ltrim($node_url), 0, 8) != 'https://') ? $this->EE->functions->fetch_site_index() : '') . $node_url;
						}
						
					}
					
					// get rid of double slashes, and trailing slash
					$node_url 	= rtrim($this->EE->functions->remove_double_slashes($node_url), '/');
					$viewed_url = rtrim($this->EE->functions->remove_double_slashes($viewed_url), '/');
					
					if($node_url === $viewed_url)
					{
						$active = $options['node_active_class'];
					}
					
					$children = '';
					$children_class = '';
					
					//print_r($array);
					
					if(isset($data['has_children']))
					{
						$children = 'yes';
						$children_class = 'has_children';
					}    		
					
					//echo $children;
					
					
					$data['label'] - $this->allow_eecode($data['label']);
					
					$variables = array(
										'node_id' => $data['node_id'],
										'node_title' => $data['label'], 
										'node_url' => $node_url,
										'node_active' => $active,
										'node_active_parent' => $active_parent,
										'node_lft' => $data['lft'],
										'node_rgt' => $data['rgt'],
										'node_entry_id' =>  $data['entry_id'],
										'node_custom_url' => $data['custom_url'],
										'node_extra' =>  $data['extra'],
										'node_entry_title' => $data['title'],
										'node_entry_url_title' => $data['url_title'],
										'node_entry_status' =>  $data['status'],
										'node_entry_entry_date' => $data['entry_date'],
										'node_entry_template_name' => $data['template_name'],
										'node_entry_template_group_name' => $data['group_name'],
										'node_has_children' => $children,
										'node_next_child' => $data['lft']+1,
										'node_level' => $data['level'],
										'node_level_count' => $level_count,
										'node_level_total_count' => $level_total_count 
										);
					
					$custom_fields = (isset($data['extra'])) ? unserialize($data['extra']) : NULL;
					
					if(is_array($custom_fields))
					{
						foreach($custom_fields as $label => $field_data)
						{
							$variables[$label] = $field_data;
						}
	
					}
					
					$tmp = $this->EE->functions->prep_conditionals($tagdata, $variables);
					
					// make sure each node has a unique class
					if($data['entry_id'] == "")
					{
						$this->EE->load->helper('url');
						$unique_class = str_replace(".","_", url_title(strtolower($data['label'])));
					}
					else
					{
						$unique_class = $data['url_title'];
					}
					
					$level = $data['level'];
					
					// build our node class and remove any extra spaces
					$node_class = preg_replace('/\s\s+/', ' ', "node_$unique_class level_$level $children_class $active_parent $active");
					
					// get rid of any space on the end
					$node_class = rtrim($node_class, " ");
										
					$str .= '<li class="'.$node_class.'">';
					$str .= "";
					$str .= $this->EE->functions->var_swap($tmp, $variables);
					
					if(isset($data['children']) && $data['level'] < $options['depth'])
					{
						// reset css id and class if going deeper
						$options['ul_css_id'] = NULL;
						$options['ul_css_class'] = NULL;
						
						// recurse dammit
						$str .= $this->EE->mpttree->build_list($data['children'], $tagdata, $options);
					}
					
					$str .= "   </li>\n";
					
				}
				else
        		{
        			$opening_ul = '';
					$closing_ul = '';
        		}
        	}
        	
        } 
           
        $str = $opening_ul.$str.$closing_ul;
        
   	 	return $str;
    }
    
 
 
 	// lifted straight from the allow EE code plugin...
    function allow_eecode($str)
    {
        $this->EE =& get_instance();

        $query = ( ! $this->EE->TMPL->fetch_param('query') ) ? 'n' : $this->EE->TMPL->fetch_param('query');
        $embed = ( ! $this->EE->TMPL->fetch_param('embed') ) ? 'n' : $this->EE->TMPL->fetch_param('embed');

        if ($query != 'yes')
        {
        	$str = preg_replace("/&#123;exp:query(.*?)&#125;/","TgB903He0mnv3dd098$1TgB903He0mnv3dd099",$str);
			$str = str_replace('&#123;/exp:query&#125;', 'Mu87ddk2QPoid990iod', $str);
        }

		if ($embed != 'yes')
		{
			$str = str_replace('&#123;embed', 'a9f83fa8b65b27e43a9db5fa4b2f62c8a23330e6', $str);
		}

        $array1 = array('&#123;',	'&#125;',	'{&#47;');
        $array2 = array('{',		'}',		'{/');

        $str = str_replace($array1,$array2,$str);

		if (preg_match_all("#\{.+?}#si", $str, $matches))
		{
			for ($i = 0, $total = count($matches[0]); $i < $total; $i++)
			{
				$str = str_replace($matches['0'][$i],
									str_replace(array('&#8220;', '&#8221;', '&#8216;','&#8217;'), array('"', '"', "'", "'"), $matches['0'][$i]),
									$str);
			}	
		}

        if ($query == 'n')
        {
        	$str = str_replace('TgB903He0mnv3dd098', '&#123;exp:query', $str);
        	$str = str_replace('TgB903He0mnv3dd099', '&#125;', $str);
			$str = str_replace('Mu87ddk2QPoid990iod', '&#123;/exp:query&#125;', $str);
        }

		if ($embed == 'n')
		{
			$str = str_replace('a9f83fa8b65b27e43a9db5fa4b2f62c8a23330e6', '&#123;embed', $str);
		}

 		$this->return_data = $str;
	}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // returns a page_uri from an entry_id
    function entry_id_to_page_uri($entry_id, $site_id = '1')
	{
		
		$site_pages = $this->EE->config->item('site_pages');
				    				
		if ($site_pages !== FALSE && isset($site_pages[$site_id]['uris'][$entry_id]))
		{
			$node_url = $site_pages[$site_id]['uris'][$entry_id];
		}
		else
		{
			// not sure what else to do really?
			$node_url = NULL;
		}
		
		return $node_url;
		
	}
	
	
	
	
	// http://www.dotvoid.com/2007/09/reordering-nested-sets-using-php-and-javascript/
	function depth2nestedset($data)
	{
	  $lines = explode("\n", $data);
	  $rows = array();
	  $stack = array();
	
	  $lft = 0;   // Left value
	  $rgt = 0;   // Right value
	  $plvl = -1; // Previous node level
	
	  	foreach($lines as $line) 
	  	{
			list($id, $lvl) = explode(',', $line);
	
	    	// Skip empty/faulty lines
	    	if (trim($id) == '') 
	    	{
				continue;
	    	}
	
	    if ($lvl > $plvl) {
	      $lft++;
	      $rgt = 0;
	      array_push($stack, $id);
	    }
	    else if ($lvl == $plvl) {
	      $pid = array_pop($stack);
	      $rows[$pid][2] = $rows[$pid][1] + 1;
	      $lft = $lft + 2;
	      $rgt = 0;
	      array_push($stack, $id);
	    }
	    else {
	      $lft = $lft + ($plvl - $lvl) + 2;
	
	      $diff = $plvl - $lvl + 1;
	      for($n = 0; $n < $diff; $n++) {
	        $pid = array_pop($stack);
	        $rows[$pid][2] = $lft - $diff + $n;
	      }
	      array_push($stack, $id);
	    }
	
	    $rows[$id] = array($id, $lft, $rgt);
	    $plvl = $lvl;
	  }
	
	  $plvl++;
	  $cnt = count($rows) * 2;
	  $leftovers = count($stack);
	
	  for($n = 0; $n < $leftovers; $n++) {
	    $pid = array_pop($stack);
	    $rows[$pid][2] = $cnt - $plvl-- + $n;
	  }
	
	  return $rows;
	}
	
	// sorts an array based on specifid value
	// eg, handles sorting of the custom fields
	function array_sort($array, $on, $order=SORT_ASC)
	{
	    $new_array = array();
	    $sortable_array = array();
		
		
		
	    if (count($array) > 0 && is_array($array)) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[$k] = $v;
	            }
	        }
	
	        switch ($order) {
	            case SORT_ASC:
	                asort($sortable_array);
	            break;
	            case SORT_DESC:
	                arsort($sortable_array);
	            break;
	        }
	
	        foreach ($sortable_array as $k => $v) {
	            $new_array[$k] = $array[$k];
	        }
	    }
	
	    return $new_array;
	}	
	
	
}
/**
 * @}
 */
?>