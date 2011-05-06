<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Taxonomy Tree (Ttree) Library
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Taxonomy Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */


/*
    This file is part of Ttree, and uses methods from the CodeIgniter MPTtree library by 
    Martin Wernstahl <m4rw3r@gmail.com> which is also distrubuted under LGPL. 
    (http://codeigniter.com/forums/viewthread/74114/)

    Ttree is free software; you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    Ttree is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Ttree {

	var 	$module_name = "taxonomy";
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->_form_base_url 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->_base_url	 	= (defined('BASE')) ? BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy' : '';
		$this->_theme_base_url 	= $this->EE->config->item('theme_folder_url').'third_party/taxonomy_assets/';
		$this->site_id	 		= $this->EE->config->item('site_id');
	}
	
	// ----------------------------------------------------------------
	
	
	/**
	 * Get Channels
	 * Can't use ELlisLab version because they include logged in member permissions which we don't want
	 */
	function get_channels($site_id = NULL)
	{
		$site_id = $this->EE->config->item('site_id');
		$this->EE->db->select('channel_title, channel_name, channel_id, cat_group, status_group, field_group');
		$this->EE->db->where('site_id', $site_id);
		$this->EE->db->order_by('channel_title');
		return $this->EE->db->get('channels')->result_array(); 
	}


	// ----------------------------------------------------------------
	
	
	/**
	 * Check if the supplied member group has permissions to access the taxonomy module in the cp
	 *
	 * Returns TRUE / FALSE
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function can_access_taxonomy($group_id = '')
	{	

		$this->EE->db->select('modules.module_id, module_member_groups.group_id');
		$this->EE->db->where('LOWER('.$this->EE->db->dbprefix.'modules.module_name)', 'taxonomy');
		$this->EE->db->join('module_member_groups', 'module_member_groups.module_id = modules.module_id');
		$this->EE->db->where('module_member_groups.group_id', $group_id);
		
		$query = $this->EE->db->get('modules');
		
		return ($query->num_rows() != 0) ? TRUE : FALSE;

	}
	
	// ----------------------------------------------------------------
	
	
	/**
	 * Builds the taxonomy_tree_x table for new trees
	 *
	 * Returns TRUE 
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function build_tree_table($tree_id = '')
	{
	
		$fields = array(
			'node_id'			=> array('type' 		 => 'mediumint',
										'constraint'	 => '8',
										'unsigned'		 => TRUE,
										'auto_increment' => TRUE,
										'null' => FALSE),
																
			'lft'				=> array('type'			=> 'mediumint',
										'constraint'	=> '8',
										'unsigned'	=>	TRUE),
										
			'rgt'				=> array('type'			=> 'mediumint',
										'constraint'	=> '8',
										'unsigned'	=>	TRUE),
										
			'moved'				=> array('type'			=> 'tinyint',
										'constraint'	=> '1',
										'null' => FALSE),
																	
			'label'				=> array('type' => 'varchar', 
										'constraint' => '255'),
										
			'entry_id'			=> array('type'			=> 'int',
										'constraint'	=> '10', 
										'null' => TRUE),
										
			'template_path'		=> array('type' => 'varchar', 
										'constraint' => '255'),							
										
			'custom_url'		=> array('type' => 'varchar', 
										'constraint' => '250', 
										'null' => TRUE),
										
			'field_data'		=> array('type' => 'text')	
										
			);
			
		$this->EE->load->dbforge();
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('node_id', TRUE);
		$this->EE->dbforge->create_table('taxonomy_tree_'.$tree_id);
		
		unset($fields);
				
	}
	
	
	// ----------------------------------------------------------------
	
	
	/**
	 * Adds a tree's settings to the users session cache and returns an array of the tree's settings
	 *
	 * @access	public
	 * @param	int
	 * @return	array
	 */ 
	function get_tree_settings($tree_id)
	{
		$id = (isset($tree_id)) ? $tree_id : 0;
		
		if($id == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_such_tree'));
			$this->EE->functions->redirect($this->base);
		}
		
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$id]['settings']))
		{
			$tree_info = array();
			$query = $this->EE->db->get_where('exp_taxonomy_trees', array('id' => $id), 1, 0);
			foreach ($query->result() as $row)
			{
				$tree_info['site_id'] = $row->site_id;
				$tree_info['label'] = $row->label;
				$tree_info['template_preferences'] = $row->template_preferences;
				$tree_info['channel_preferences'] = $row->channel_preferences;
				$tree_info['last_updated'] = $row->last_updated;
				$tree_info['fields'] = ($row->fields != '') ? $this->unserialize($row->fields) : '';
				$tree_info['permissions'] = $row->permissions;
			}
			
			$this->EE->session->cache['taxonomy']['tree'][$id]['settings'] = $tree_info;

		}
		
		return $this->EE->session->cache['taxonomy']['tree'][$id]['settings'];
		
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Get Template
	 *
	 * @access	public
	 * @param	(array) template ids
	 * @param	(int) tree_id 
	 * @return	array
	 */
	function get_templates($template_ids, $tree_id)
	{
		
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['templates']))
		{
			$site_id = $this->EE->config->item('site_id');
			$this->EE->session->cache['taxonomy']['tree'][$tree_id]['templates'] = array();
	
			$this->EE->db->select("template_id, template_name, group_name");
			$this->EE->db->from("templates");
			$this->EE->db->join("template_groups", "templates.group_id = template_groups.group_id");
			$this->EE->db->where('templates.site_id', $site_id);
			$this->EE->db->where_in('templates.template_id', $template_ids);
			$this->EE->db->order_by('group_name, template_name');
			$templates = $this->EE->db->get()->result_array();
			
			foreach( $templates as $template )
			{
				$this->EE->session->cache['taxonomy']['tree'][$tree_id]['templates'][$template['template_id']] = $template;
			}

		}

		return $this->EE->session->cache['taxonomy']['tree'][$tree_id]['templates'];
		
	}
	
	
	// --------------------------------------------------------------------
	
	
	
	///////////////////////////////////////////////
	// T E H  N E S T E D  S E T  R U L E Z,  YO
	///////////////////////////////////////////////



	// --------------------------------------------------------------------
	
	/**
	 * Declares the table which the class operates on.
	 * @param $table_name The name of the new table
	 * @return void
	 */
	function set_table($tree_id)
	{
		if($tree_id != null || $tree_id != '')
		{
			$this->tree_table 	= 'exp_taxonomy_tree_'.$tree_id;
			$this->tree_id 		= $tree_id;
			$this->left_col 	= 'lft';
			$this->right_col 	= 'rgt';
			$this->id_col 		= 'node_id';
			$this->title_col 	= 'label';
			return;
		}
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Converts the tree structure in the tree to an array.
	 *
	 * NOTE: Used for storing a serialised array of the specified tree in the exp_taxonomy_trees table (tree_array)
	 * Most menu generation happens from this array, so storing this should see a performance boost as we have several joins happening
	 * to build the array.
	 * also used when we're calling a tree and we're not using the root 
	 * 
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
		function tree_to_array($root = 1, $root_entry_id = NULL, $root_node_id = NULL, $tree_id = NULL)
		{
			
			if($root_entry_id)
			{
				$node = $this->get_node_by_entry_id($root_entry_id, $tree_id);
			}
			elseif($root_node_id)
			{
				$node = $this->get_node_by_node_id($root_node_id);
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
				'.$this->tree_table.'.field_data, ';
			
			
			$query .=  'exp_statuses.status,
						exp_statuses.highlight,
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
					
						LEFT JOIN exp_statuses
						ON (exp_statuses.status=exp_channel_titles.status)';

			$query .=	' WHERE ('.$this->left_col.
						' BETWEEN '.$node[$this->left_col].
						' AND '.$node[$this->right_col].')';

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
			
			foreach($query->result_array() as $row)
			{
			
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
				if(count($right) > $lastlevel)
				{
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
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Builds the ordered list for the module tree interface
	 * @param array
	 * @return string
	 */
	function build_cp_list($array, $ind = '')
	{
		// root node is not sortable, so don't include in the <ol>
		if($array[0]['level'] == 0)
		{
			$str = "<h3>";
		}
		// first <ol> has our id
		elseif($array[0]['level'] == 1)
		{
			$str = "<ol id='taxonomy-list'>\n";
		}
		// child branches are regular <ol>
		else
		{
			$str = "<ol>";
		}
    	
    	foreach($array as $data)
    	{
    		$node_id			= $data['node_id'];
    		$tree_id			= $this->EE->input->get('tree_id');
    		
    		// if the template group is the site default, hide it.
    		$template_group 	= '/'.$data['group_name']; 
    		
    		// hide /index templates from the urls
    		$template_name		= ($data['template_name'] != 'index') ? '/'.$data['template_name'] : '';
    		
    		$url_title 			= '/'.$data['url_title'];
    		$custom_url			= (isset($data['custom_url'])) ? $data['custom_url'] : '';
    		$children 			= (isset($data['children'])) ? $data['children'] : '';
    		$label				= $data['label'];
    		$highlight			= $data['highlight'];
    		$status				= ($data['status'] && $data['status'] != 'open') ? '<em class="status_indicator" title="'.ucfirst($data['status']).'" style="background-color: #'.$highlight.'">['.ucfirst($data['status']).']</em>' : '';
    		$site_url			= $this->EE->functions->fetch_site_index();
    		$level 				= $data['level'];
    		
    		// indentation
    		$ind				= str_repeat('	', $data['level']+1);

    		// build the edit entry link
    		$edit_entry_link	= BASE.'&amp;C=content_publish&amp;M=entry_form&amp;channel_id='.$data['channel_id'].'&amp;entry_id='.$data['entry_id'];
    		
    		// build the delete node/branch link (if its a branch, we delete all children too)
    		$delete_branch 	= "$this->_base_url&amp;method=delete_branch&amp;node_id=$node_id&amp;tree_id=$tree_id";
    		$delete_node 	= "$this->_base_url&amp;method=delete_node&amp;node_id=$node_id&amp;tree_id=$tree_id";
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
    			// does the custom url start with http:// or https://, 
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
			
			$str .= ($array[0]['level'] != 0) ? $ind."<li id='list_$node_id'>\n" : '';
			
			
			$str .= $ind."	<div class='item-wrapper'><div class='item-handle'></div>\n";
        	$str .= $ind."		$status <a href='$this->_base_url&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id'>$label</a>\n"; 
        	$str .= $ind."		<div class='item-options'> \n";
			if($data['entry_id'])
			{
				$str .= $ind."  		<a href='$edit_entry_link'>Edit Entry</a> \n";
			}
			$str .= $ind."  		<a href='$node_url' target='_blank' title='Visit: $node_url'>Visit Page</a> \n";
			$str .= $ind."  		<a href='$this->_base_url&amp;method=manage_node&amp;node_id=$node_id&amp;tree_id=$tree_id'>Edit Node</a> \n";
			$str .= $ind."  		<a href='$delete_link' class='delete_$delete_class'>x</a> \n";
			$str .= $ind."		</div> \n";
        	$str .= $ind."	</div> \n\n";
        	
        	if($array[0]['level'] == 0)
			{
				$str .= "</h3>";
			}
        	
        	if($children)
        	{
        		// recurse!
	            $str .= $this->build_cp_list($children, $ind);
	        }

        	$str .= ($array[0]['level'] != 0) ? "</li>" : '';
        	
        }    

        $str .= ($array[0]['level'] != 0) ? "</ol>" : '';
        
 
   	 	return $str;
    }
	
	
	// --------------------------------------------------------------------
	
	
	 /**
	 * Returns all nodes from a tag pair and produces a nested unordered list
	 *
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
		$options['entry_status'] 	= ($options['entry_status']) ? $options['entry_status'] : array('open');
		$options['style'] 			= ($options['style']) ? $options['style'] : 'nested';
		
		if (! isset($this->EE->session->cache['taxonomy_node_count']))
		{
			$this->EE->session->cache['taxonomy_node_count'] = 1;
		}
		
		if (! isset($this->EE->session->cache['taxonomy_node_previous_level']))
		{
			$this->EE->session->cache['taxonomy_node_previous_level'] = 0;
		}

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
		
    	$opening_ul = ($options['style'] == 'nested') ? "<ul".$ul_id.$ul_class.">\n" : '';		
		$closing_ul = ($options['style'] == 'nested') ? "</ul>\n" : '';
		
		// counts for level count vars and first/last css classes
		$level_count = 0;
        $level_total_count = 0;
        
        // do a loop to count how many nodes 
        // are within our selected statuses for this level
        foreach($array as $data)
	    { 
	    	if(($data['status'] == "" ||  in_array($data['status'], $options['entry_status'])) || ($options['entry_status'] == array('ALL')))
	    	{
	    		$level_total_count++;
	    	}
	    }
	    
	    unset($data);
		
    	foreach($array as $data)
	    {    

			// only parse items with selected statuses
	    	if(($data['status'] == "" ||  in_array($data['status'], $options['entry_status'])) || ($options['entry_status'] == array('ALL')))
	    	{
				
		    	$active_parent = '';
		    	$level_count++;

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
	    		 $str = $this->build_list($data['children'], $tagdata, $options);
	    		 $opening_ul = '';
	    		 $closing_ul = '';
	    		}
	    		else
	    		{
	    			$options['node_count'] = $this->EE->session->cache['taxonomy_node_count']++;
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
							|| $data['level'] <= $options['active_branch_start_level']
						)
					{
						// remove default template group segments
						$template_group = ($data['is_site_default'] == 'y' && $options['hide_dt_group'] == 'yes') ? '' : '/'.$data['group_name'];
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
											'node_field_data' =>  $data['field_data'],
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
											'node_level_total_count' => $level_total_count,
											'node_count' => $options['node_count'],
											'node_previous_level' => $this->EE->session->cache['taxonomy_node_previous_level'],
											'node_previous_level_diff' => $this->EE->session->cache['taxonomy_node_previous_level'] - $data['level']
											);
						
						// update with our new level
						$this->EE->session->cache['taxonomy_node_previous_level'] = $data['level'];
						
						$custom_fields = (isset($data['field_data'])) ? $this->unserialize($data['field_data']) : NULL;
						
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
						
						$first_class = ($level_count == 1 && $level) ? 'first_child' : '';
						$last_class = ($level_count == $level_total_count && $level) ? 'last_child' : '';

						// build our node class and remove any extra spaces
						$node_class = preg_replace('/\s\s+/', ' ', "node_$unique_class level_$level $children_class $active_parent $active $first_class $last_class");
						
						// get rid of any space on the end
						$node_class = rtrim($node_class, " ");
											
						$str .= ($options['style'] == 'nested') ? '<li class="'.$node_class.'">' : '';
						$str .= "";
						$str .= $this->EE->functions->var_swap($tmp, $variables);
						
						if(isset($data['children']) && $data['level'] < $options['depth'])
						{
							// reset css id and class if going deeper
							$options['ul_css_id'] = NULL;
							$options['ul_css_class'] = NULL;
							
							// recurse dammit
							$str .= $this->build_list($data['children'], $tagdata, $options);
						}
						
						$str .= ($options['style'] == 'nested') ? "   </li>\n" : '';
						
					}
					else
	        		{
	        			$opening_ul = '';
						$closing_ul = '';
	        		}
	        	}
	        	
	        } // end status check
        
        } // end foreach $array as $data
           
        $str = $opening_ul.$str.$closing_ul;

   	 	return $str;
    }
	
	
	
	///////////////////////////////////////////////
	//  Get functions
	///////////////////////////////////////////////
	
	/**
	 * Returns the root node array.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_root()
	{
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => 1),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		return $return;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Returns the entire tree array stored in the exp_taxonomy_trees table.
	 * @return array,
	 * but if no rows returned, false
	 */
	function get_tree_array($tree_id)
	{
		$this->EE->db->select('tree_array');
		$query = $this->EE->db->get_where('exp_taxonomy_trees',array('id' => $tree_id),1);
		$tree_array = $query->num_rows() ? $query->row_array() : false;
		$return = isset($tree_array['tree_array']) ? $this->unserialize($tree_array['tree_array']) : false;
		return $return;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get a flat array of nodes
	 *
	 * @param 	integer 	$root		The lft of the node to be root in the query, default: 1
	 * @return 	mixed		integer 	integer Level of the Node (0 = Root) or boolean False
	 */
	 function get_flat_tree($root = 1)
	 {
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
		foreach($query->result_array() as $row)
		{

			// go more shallow, if needed
			if(count($right))
			{
				while($right[count($right)-1] < $row[$this->right_col])
				{
					array_pop($right);
				}
			}
			// Go one level deeper?
			if(count($right) > $level)
			{
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
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Returns the node with lft value of $lft.
	 * @param $lft The lft of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node($lft)
	{
		$query = $this->EE->db->get_where($this->tree_table,array($this->left_col => $lft),1);
		return $query->num_rows() ? $query->row_array() : false;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns the node with id of $id.
	 * @param $id The id of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_node_by_node_id($id)
	{
		$query = $this->EE->db->get_where($this->tree_table,array($this->id_col => $id),1);
		$return = $query->num_rows() ? $query->row_array() : false;
		if(!$return)
			echo 'Node with '.$this->id_col.' '.$id.' was not found.';
		return $return;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns the entry_ids of nodes
	 * @param $id The id of the requested node.
	 * @return An asociative array with the table row,
	 * but if no rows returned, false
	 */
	function get_tree_entry_ids($tree_id)
	{
		$this->EE->db->select('entry_id');
		$query = $this->EE->db->get_where($this->tree_table, array('entry_id !=' => ''));
		return $query->num_rows() ? $query->result_array() : false;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns the node with the requested entry_id.
	 * @param $id The id of the requested node.
	 * @param $tree_id
	 * @return array of node's data
	 */
	function get_node_by_entry_id($entry_id){
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['entry_'.$entry_id]))
		{
			$query = $this->EE->db->get_where($this->tree_table,array('entry_id' => $entry_id),1);
			$this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['entry_'.$entry_id] = ($query->num_rows()) ? $query->row_array() : false;
		}
		return $this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['entry_'.$entry_id];
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns an array of nodes from the node with $lft & $rgt to the root
	 * @param $left The lft of the requested node.
	 * @param $rgt The rgt of the requested node.
	 * @return array
	 */
	function get_parents_crumbs($lft,$rgt){
	
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['crumbs_array'][$lft.'|'.$rgt]))
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
					'.$this->tree_table.'.field_data, 
					
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
			$this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['crumbs_array'][$lft.'|'.$rgt] = ($query->num_rows()) ? $query->result_array() : array();
		}
		
		// print_r($this->EE->session->cache['taxonomy']);
		
		return $this->EE->session->cache['taxonomy']['tree'][$this->tree_id]['crumbs_array'][$lft.'|'.$rgt];
	
	}
	
	// --------------------------------------------------------------------

	/**
	 * Returns the entire tree array stored in the exp_taxonomy_trees table.
	 * @param url_title
	 * @return int entry_id,
	 * but if no rows returned, false
	 */
	function get_entry_id_from_url_title($url_title)
	{
        $this->EE->db->where('url_title', $url_title)->limit(1);
        $entry = $this->EE->db->get('exp_channel_titles')->row_array();            
        if($entry)
        {
            return $entry['entry_id'];
        }
        return false;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Returns the closest related parent.
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

	// --------------------------------------------------------------------
	
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
	
	// --------------------------------------------------------------------

	//////////////////////////////////////////
	//  Insert functions
	//////////////////////////////////////////	
	
	/**
	 * Creates the root node in the table.
	 * @param $data The root node data
	 * @return true if success, but if rootnode exists, it returns false
	 */
	function insert_root($data)
	{
		$this->lock_tree_table(); // Lock table first then check if root exits - I am being pedantic in the sequence of these statements.
		if($this->get_root() != false) 
		{
			$this->unlock_tree_table();
			return false;
		}
		$data = $this->sanitize_data($data);
		$data = array_merge($data,array($this->left_col => 1,$this->right_col => 2));
		$this->EE->db->insert($this->tree_table,$data);
		$this->unlock_tree_table();
		return true;
	}
	
	
	// --------------------------------------------------------------------
	
	
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
	
	
	// --------------------------------------------------------------------
	
	
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
	
	
	// --------------------------------------------------------------------
	
	
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
	
	
	// --------------------------------------------------------------------
	
	
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
	
	
	// --------------------------------------------------------------------
	
	
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
	function insert_node($lft,$data,$lock = true)
	{
		$root = $this->get_root();
		if($lft > $root[$this->right_col] || $lft < 1)return false;
		$data = $this->sanitize_data($data);
		
		if ($lock)
			$this->lock_tree_table();

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
	
	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////
	//  Update functions
	//////////////////////////////////////////
	
	/**
	 * Updates the node values.
	 * @param $lft The lft of the node to be manipulated
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return true if success, false otherwise
	 */
	function update_node($lft,$data)
	{
		if(!$this->get_node($lft))return false;
		$data = $this->sanitize_data($data);
		// Make the update
		$this->EE->db->where($this->left_col,$lft);
		$this->EE->db->update($this->tree_table,$data);
		return true;
	}
	
	
	/**
	 * Updates the node values.
	 * @param $lft The lft of the node to be manipulated
	 * @param $data The data to be inserted into the row (associative array, key = column).
	 * @return true if success, false otherwise
	 */
	function update_node_by_node_id($node_id,$data)
	{
		if(!$this->get_node_by_node_id($node_id))return false;
		$data = $this->sanitize_data($data);
		// Make the update
		$this->EE->db->where($this->id_col,$node_id);
		$this->EE->db->update($this->tree_table,$data);
		return true;
	}
	
	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////
	//  Lock functions
	//////////////////////////////////////////

	/**
	 * Locks tree table.
	 * This is a straight write lock - the database blocks until the previous lock is released
	 */
	function lock_tree_table($aliases = array())
	{
		$q = "LOCK TABLE " . $this->tree_table . " WRITE";
		$res = $this->EE->db->query($q);
	}

	/**
	 * Unlocks tree table.
	 * Releases previous lock
	 */
	function unlock_tree_table()
	{
		$q = "UNLOCK TABLES";
		$res = $this->EE->db->query($q);
	}
	
	
	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////////
	//  Delete functions
	//////////////////////////////////////////////
	
	/**
	 * Deletes the node with the lft specified and promotes all children.
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_node($lft)
	{
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] <= 1)
			return false;
		// Lock table
		$this->lock_tree_table();
		$this->EE->db->where($this->id_col,$node[$this->id_col]);
		$this->EE->db->delete($this->tree_table);
		$this->remove_gaps(); // HCG: I do not like this - should be deprecated
		$this->unlock_tree_table();
		return true;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Deletes the node with the lft specified and all children.
	 * @param $lft The lft of the node to be deleted
	 * @return True if something was deleted, false if not
	 */
	function delete_branch($lft)
	{
		$node = $this->get_node($lft);
		if(!$node || $node[$this->left_col] == 1)
			return false;
		// lock table
		$this->lock_tree_table();
		$this->EE->db->where($this->left_col.' BETWEEN '.$node[$this->left_col].' AND '.$node[$this->right_col]);
		$this->EE->db->delete($this->tree_table);
		$this->remove_gaps();
		$this->unlock_tree_table();
		return true;
	}
	
	
	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////////
	//  Gap functions
	//////////////////////////////////////////////
	
	/**
	 * Creates an empty space inside the tree beginning at $pos and with size $size.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @param $pos The starting position of the empty space.
	 * @param $size The size of the gap
	 * @return True if success, false if not or if space is outside root
	 */
	function create_space($pos,$size)
	{
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
	 * @return The starting pos of the gap and size
	 */
	function get_first_gap()
	{
		$ret = $this->find_gaps();
		return $ret === false ? false : $ret[0];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Removes the first gap in table.
	 * Primary for internal use.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @return True if gap removed, false if none are found
	 */
	function remove_first_gap()
	{
		$ret = $this->get_first_gap();
		if($ret !== false)
		{
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Removes all gaps in the table.
	 * @attention A lock must already beem aquired before calling this method, otherwise damage to the tree may occur.
	 * @return True if gaps are found, false if none are found
	 */
	function remove_gaps()
	{
		$ret = false;
		while($this->remove_first_gap() !== false){$ret = true;}
		return $ret;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Finds all the gaps inside the tree.
	 * Primary for internal use.
	 * @return Returns an array with the start and size of all gaps,
	 * if there are no gaps, false is returned
	 */
	function find_gaps()
	{
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
		while($max >= $current)
		{
			$val = $nums[$i];
			if($val == $current)
			{
				$old[] = $val;
				$foundgap = 0;
				$i++;
			}
			else
			{
				// have gap or duplicate
				if($val > $current)
				{
					if(!$foundgap)$gaps[] = array('start'=>$current,'size'=>1);
					else
					{
						$gaps[count($gaps) - 1]['size']++;
					}
					$foundgap = 1;
				}
			}
			$current++;
		}
		return count($gaps) > 0 ? $gaps : false;
	}
	
	
	// --------------------------------------------------------------------
	
	
	//////////////////////////////////////////////
	//  Helper functions
	//////////////////////////////////////////////
	
	/**
	 * Sanitizes the data given.
	 * Removes the left_col and right_col from the data, if they exists in $data.
	 * @param $data The data to be sanitized
	 * @return The sanitized data
	 */
	function sanitize_data($data){
		// Remove fields which potentially can damage the tree structure
		if(is_array($data))
		{
			unset($data[$this->left_col]);
			unset($data[$this->right_col]);
		}
		elseif(is_object($data))
		{
			unset($data->{$this->left_col});
			unset($data->{$this->right_col});
		}
		return $data;
	}
	
	// --------------------------------------------------------------------
	/*
	 * returns the pages module uri for a given entry
	 * @param entry_id
	 * @return string
	 */
	function entry_id_to_page_uri($entry_id)
	{
		
		$site_pages = $this->EE->config->item('site_pages');
				    				
		if ($site_pages !== FALSE && isset($site_pages[$this->site_id]['uris'][$entry_id]))
		{
			$node_url = $site_pages[$this->site_id]['uris'][$entry_id];
		}
		else
		{
			// not sure what else to do really?
			$node_url = NULL;
		}
		
		return $node_url;
		
	}
	
	// --------------------------------------------------------------------
	
	/*
	 * sets the last_updated timestamp for a tree
	 * required as nested set can get real screwed if multiple folks are editing
	 */ 
	function set_last_update_timestamp($tree_id)
	{
		$id = (isset($tree_id)) ? $tree_id : 0;
		$data = array('last_updated' => time());
		$this->EE->db->where('id', $id);
		$this->EE->db->update('exp_taxonomy_trees', $data);
	}
	
	
	// --------------------------------------------------------------------
	
	/*
	 * Checks a tree table exists, returns true if the table is found,
	 * Prevents mysql errors if exp_taxonomy_tree_x table isn't found.
	 * and adds to session array so subsequent requests don't need to hit the db
	 * @param $tree_id
	 * @param $cp_redirect - set to true if we're to bounce the user in the cp
	 * @return true/false
	 */ 
	function check_tree_table_exists($tree_id, $cp_redirect = false)
	{
	
		if(!isset( $this->EE->session->cache['taxonomy']['tree'][$tree_id]['exists']) )
		{
			if (!$this->EE->db->table_exists('exp_taxonomy_tree_'.$tree_id))
			{
				$this->EE->session->cache['taxonomy']['tree'][$tree_id]['exists'] = false;
				if($cp_redirect)
				{
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_such_tree'));
					$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy');
				}
				return false;
			}
			else
			{
				$this->EE->session->cache['taxonomy']['tree'][$tree_id]['exists'] = true;
				// set the table while we're at it.
				$this->set_table($tree_id);
			}
		}

		return true;

	}

	// --------------------------------------------------------------------
	
	
	/*
	 * Rebuilds the tree_array column in exp_taxonomy_trees for the specified tree
	 * @param $tree_id
	 * @return void
	 */
	function rebuild_tree_array($tree_id = null)
	{
		if(!$tree_id)
			return;
			
		$data = array();
		$data['tree_array'] = base64_encode(serialize( $this->tree_to_array() ));
		$this->EE->db->where('id', $tree_id);
		$this->EE->db->update('exp_taxonomy_trees', $data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * unserialize
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	protected function unserialize($data)
	{
		$data = @unserialize(base64_decode($data));
		
		return (is_array($data)) ? $data : array();
	}
	
	// --------------------------------------------------------------------

	/*
     * Added by fcco:
     * -----------------------------------------------------------------------------
     * This function iterates through the tree until the right start level is found.
     * If this is the case, go search this subset for the required entry_id.
     * When found, return this branch. Uses self-recursion.
     */
    function _find_in_tree($tree, $id, $depth, $start_level=FALSE)
    {
        foreach($tree as $item)
        {
            if($item['level'] === $start_level || $start_level === FALSE)
            {
                if($this->_find_in_subset($item, $id, $depth))
                    return $item;
            }
            else
            {
                if($item['level'] < $start_level || $start_level === FALSE)
                {
                    if(isset($item['children']))
                    {
                        $result = $this->_find_in_tree($item['children'], $id, $depth, $start_level);

                        // keep searching sublevels until hit
                        if($result !== FALSE)
                            return $result;
                    }
                }
            }
        }

        return FALSE;
    }
    
    // --------------------------------------------------------------------

    /*
     * Added by fcco:
     * -----------------------------------------------------------------------------
     * This function finds an entry inside a subset, uses self-recursion.
     */
    function _find_in_subset(&$item, $id, $depth)
    {
        if($item['level'] > $depth)
            return FALSE;

        if(isset($item['entry_id']) && $item['entry_id'] == $id)
            return TRUE;
        else
        {
            if(isset($item['children']))
            {
                $found = FALSE;

                foreach($item['children'] as $child)
                {
                    if($this->_find_in_subset($child, $id, $depth))
                        $found=TRUE;
                }

                return $found;
            }
            else
                return FALSE;
        }
    }
    
    
    // --------------------------------------------------------------------
	
	
	/*
	* returns an array of stuff... @todo
	*/
    function node_urls_array(){
	
		// need to optimise this query
		$query = $this->EE->db->query('SELECT 
		
				'.$this->tree_table.'.node_id,
				'.$this->tree_table.'.entry_id, 
				'.$this->tree_table.'.custom_url, 
				
				exp_channel_titles.entry_id, 
				exp_channel_titles.url_title,
								
				exp_templates.group_id, 
				exp_templates.template_name, 
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
    
    
    // --------------------------------------------------------------------
	
	/*
	* Not used, but will probably come in handy
	* builds an array of [node_id] => parent node_id
	* (essentially an adjacency model view of the tree)
	* @param $tree_id
	*/
	function build_adjacency_data($tree_id = 0)
	{

		if(!$tree_id)
			return;
		
		if(!isset( $this->EE->session->cache['taxonomy']['tree'][$tree_id]['adjacency_node_ids']) )
		{
			$pc_array = array();
			
			$query = 'SELECT
				node.label AS name,
	           	node.node_id,
	           	parent.label AS parent_name,
	            parent.node_id AS parent_node_id
	            
				FROM exp_taxonomy_tree_'.$tree_id.' AS node
				
	           	LEFT JOIN exp_taxonomy_tree_'.$tree_id.' AS parent
	           	
	           	ON parent.lft = (
	                SELECT           MAX(rel.lft)
	                FROM             exp_taxonomy_tree_'.$tree_id.' AS rel
	                WHERE            rel.lft < node.lft AND rel.rgt > node.rgt
	            )
	
				ORDER BY node.lft ASC;';
	
			$query = $this->EE->db->query($query);	
			
			foreach($query->result_array() as $row)
			{
				$pc_array[ $row['node_id'] ] = $row['parent_node_id'];
			}
			
			$this->EE->session->cache['taxonomy']['tree'][$tree_id]['adjacency_node_ids'] = $pc_array;
			
		}
		
		return $this->EE->session->cache['taxonomy']['tree'][$tree_id]['adjacency_node_ids'];
	
	}
	



}
// END CLASS

/* End of file Ttree.php */
/* Location: ./system/expressionengine/third_party/taxonomy/libraries/Ttree.php */