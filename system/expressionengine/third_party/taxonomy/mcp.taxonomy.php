<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
 * Taxonomy Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

// ------------------------------------------------------------------------

class Taxonomy_mcp {
	
	public $return_data;
	
	private $_form_base_url;
	private $_base_url;
	private $_theme_base_url;
	var 	$module_name = "taxonomy";
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		// define some vars
		$this->_form_base_url 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->_base_url		= BASE.AMP.$this->_form_base_url;
		$this->_theme_base_url 	= $this->EE->config->item('theme_folder_url').'third_party/taxonomy_assets/';
		$this->site_id	 		= $this->EE->config->item('site_id');
		
		$this->EE->load->helper(array('form', 'taxonomy'));
		
		$this->EE->load->library('table');
		$this->EE->load->library('Ttree');
		
		$this->EE->cp->set_right_nav(array(
			'module_home'	=> $this->_base_url,
			'add_tree'		=> $this->_base_url.AMP.'method=edit_tree'.AMP.'new=1'
		));
		
		$this->EE->cp->add_to_head('<link type="text/css" href="'.$this->_theme_base_url.'css/taxonomy.css" rel="stylesheet" />');

	}
	
	
	// ----------------------------------------------------------------


	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
	
		$this->EE->cp->set_variable('cp_page_title', lang('taxonomy_module_name'));

		$vars = array();
		
		// fetch our trees
		$trees = $this->EE->db->get_where('exp_taxonomy_trees',array('site_id' => $this->site_id))->result_array();
		
		if( !$trees )
		{
			// no trees exist, get started message
			$vars['title_extra'] = lang('get_started');
			return $this->content_wrapper('newbie', 'welcome', $vars);
		}

		foreach ($trees as $tree)
		{
			// check permissions for each tree
			if( has_access_to_tree($this->EE->session->userdata['group_id'], $tree['permissions']) )
			{
				$vars['trees'][$tree['id']]['id'] = $tree['id'];
				$vars['trees'][$tree['id']]['site_id'] = $tree['site_id'];
				$vars['trees'][$tree['id']]['tree_label'] = $tree['label'];
				$vars['trees'][$tree['id']]['edit_tree_link'] = $this->_base_url.AMP.'method=edit_tree'.AMP.'tree_id='.$tree['id'];
				$vars['trees'][$tree['id']]['edit_nodes_link'] = $this->_base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree['id'];
				$vars['trees'][$tree['id']]['delete_tree_link'] = $this->_base_url.AMP.'method=delete_tree'.AMP.'tree_id='.$tree['id'];
			}
		}
		
		return $this->content_wrapper('index', 'manage_trees', $vars);
		
	}


	// ----------------------------------------------------------------


	/**
	 * Edit an existing tree, or add a new tree form
	 *
	 * @access	public
	 */
	function edit_tree()
	{

		$tree_id = $this->EE->input->get('tree_id');
		$this->EE->ttree->check_tree_table_exists($tree_id);
		
		$this->EE->cp->add_to_head('<link type="text/css" href="'.$this->_theme_base_url.'css/taxonomy.css" rel="stylesheet" />');

		$new = $this->EE->input->get('new');
		
		$vars = array();
		
		// fetch the tree
		$this->EE->db->where_in('id', $tree_id);
		$vars['tree'] = $this->EE->db->get('taxonomy_trees')->result_array();
		
		// make sure if a tree is requested it exists
		// unless we're adding a new one that is...
		if( !$vars['tree'] &&  $new != 1)
		{
			$this->EE->session->set_flashdata( 'message_failure', lang('invalid_tree') );
			$this->EE->functions->redirect($this->_base_url);
		}
		
		
		
		$vars['tree'] = (isset($vars['tree'][0])) ? $vars['tree'][0] : '';
		$vars['tree']['template_preferences'] = (isset($vars['tree']['template_preferences'])) ? explode('|', $vars['tree']['template_preferences']) : '';
		$vars['tree']['channel_preferences'] = (isset($vars['tree']['channel_preferences'])) ? explode('|', $vars['tree']['channel_preferences']) : '';
		$vars['tree']['permissions'] = (isset($vars['tree']['permissions'])) ? explode('|', $vars['tree']['permissions']) : '';
		
		$vars['tree']['fields'] = (isset($vars['tree']['fields'])) ? array_sort($this->unserialize($vars['tree']['fields']), 'order', SORT_ASC) : '';

		
		// -----------
		// Build templates, channels, and member_group select options
		// -----------
		
			// fetch our templates
			$templates = $this->EE->template_model->get_templates($this->site_id)->result_array();
			
			// must have templates!
			if ( !$templates )
			{
				$this->EE->session->set_flashdata('message_failure', lang('no_templates_exist'));
				$this->EE->functions->redirect($this->_base_url);
			}
			
			// build up our templates array for our multiselect options
			foreach( $templates as $template )
			{
				$vars['templates'][$template['template_id']] = '/'.$template['group_name'].'/'.$template['template_name'];
			}

			// fetch our channels
			$channels = $this->EE->ttree->get_channels($this->site_id);
			
			// must have channels!
			if (!$channels)
			{
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_channels_exist'));
				$this->EE->functions->redirect($this->_base_url);
			}
			
			// build our channels array for our multiselect options
			foreach( $channels as $channel )
			{
				$vars['channels'][$channel['channel_id']] = $channel['channel_title'];
			}

			// fetch our member_groups
			$member_groups = $this->EE->member_model->get_member_groups()->result_array();
			
			// build our member_groups array for our multiselect options
			foreach( $member_groups as $member_group )
			{
				// only add to the array if the member group can actuall access the taxonomy module 
				if( $this->EE->ttree->can_access_taxonomy($member_group['group_id']) )
				{
					$vars['member_groups'][$member_group['group_id']] = $member_group['group_title'];
				}
				else
				{
					$vars['member_groups'] = NULL;
				}
			}
			
		
		// -----------

		if($new)
		{
			$vars['tree']['id'] = NULL;
			$vars['tree']['label'] = NULL;
			return $this->content_wrapper('edit_tree', 'add_tree', $vars);
		}

		return $this->content_wrapper('edit_tree', 'edit_tree', $vars);
	
	}


	// ----------------------------------------------------------------


	/**
	 * Enter/update node tree data to exp_taxonomy_trees, 
	 * if new - create new tree table to hold nested set.
	 *
	 * @access	public
	 */
	function update_tree()
	{
	
		$data = array();
		$field_prefs = array();
		
		// get all our post data
		$data['id'] 		= $this->EE->input->post('id');
		$data['site_id']	= $this->site_id;
		$data['label']		= $this->EE->input->post('label');
		$data['fields'] 	= $this->EE->input->post('fields');
		$data['template_preferences'] = $this->EE->input->post('template_preferences');
		$data['channel_preferences']  = $this->EE->input->post('channel_preferences');
		$data['permissions'] = $this->EE->input->post('member_group_preferences');
		
		// implode posted array data
		$data['template_preferences'] = (is_array($data['template_preferences']) ? implode('|', $data['template_preferences']) : '');
		$data['channel_preferences']  = (is_array($data['channel_preferences'])  ? implode('|', $data['channel_preferences']) : '');
		$data['permissions']  		  = (is_array($data['permissions']) ? implode('|', $data['permissions']) : '');
		
		// prep our custom field data
		if($data['fields'] && is_array($data['fields']))
		{
			foreach($data['fields'] as $key => $field)
			{
				if($field['label'] && $field['name'])
				{
					$field_prefs[$key] = $field;
				}
			}
		}

		$data['fields'] = (count($field_prefs) > 0) ? base64_encode(serialize($field_prefs)) : '';

		$data = $this->EE->security->xss_clean($data);

		// do we have an id? update...
		if( $data['id'] )
		{
			$this->EE->db->query($this->EE->db->update_string('exp_taxonomy_trees', $data, "id = ".$data['id']));
			$this->EE->session->set_flashdata('message_success', lang('properties_updated'));
			$ret = ($this->EE->input->post('update_and_return')) ? $this->_base_url : $this->_base_url.AMP.'method=edit_tree'.AMP.'tree_id='.$data['id'];
			$this->EE->functions->redirect($ret);	
		}
		// if not then create the new tree table
		else
		{
			unset($data['id']);
			$this->EE->db->query($this->EE->db->insert_string('exp_taxonomy_trees', $data));
			$tree_id = $this->EE->db->insert_id();
			// build our tree table
			$this->EE->ttree->build_tree_table($tree_id);
			// notify of success and redirect
			$this->EE->session->set_flashdata('message_success', lang('tree_added'));
			$ret = ($this->EE->input->post('update_and_return')) ? $this->_base_url : $this->_base_url.AMP.'method=edit_tree'.AMP.'tree_id='.$tree_id;
			$this->EE->functions->redirect($ret);	
		}
	
	}


	// ----------------------------------------------------------------


	/**
	 * Nuke a tree
	 *
	 * @access	public
	 */
	function delete_tree()
	{
		
		$tree_id = $this->EE->input->get('tree_id');
		
		// @todo add confirmation of delete
		
		$this->EE->db->or_where('id', $tree_id);
		$this->EE->db->delete('exp_taxonomy_trees');
		
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('taxonomy_tree_'.$tree_id);

		$this->EE->session->set_flashdata('message_success', lang('tree_deleted'));
		$this->EE->functions->redirect($this->_base_url);
		
	}
	
	
	// ----------------------------------------------------------------


	/**
	 * Nuke a node
	 *
	 * @access	public
	 */
	function delete_node()
	{
		$tree_id = $this->EE->input->get('tree_id');
		$node_id = $this->EE->input->get('node_id');
		$this->EE->ttree->check_tree_table_exists($tree_id, true);						
		$node = $this->EE->ttree->get_node_by_node_id($node_id);
		$this->EE->ttree->delete_node($node['lft']);
		$this->EE->ttree->set_last_update_timestamp($tree_id);
		
		// altered our tree structure so rebuild our tree 
		// array column in exp_taxonomy_trees for this tree
		$data['tree_array'] = base64_encode(serialize( $this->EE->ttree->tree_to_array() ));
		$this->EE->db->where('id', $tree_id);
		$this->EE->db->update('exp_taxonomy_trees', $data); 
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('node_deleted'));
		$this->EE->functions->redirect($this->_base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
	}
	
	
	// ----------------------------------------------------------------


	/**
	 * Nuke a branch
	 * @todo combine with above function
	 *
	 * @access	public
	 */
	function delete_branch()
	{
		$tree_id = $this->EE->input->get('tree_id');
		$node_id = $this->EE->input->get('node_id');
		$this->EE->ttree->check_tree_table_exists($tree_id, true);					
		$node = $this->EE->ttree->get_node_by_node_id($node_id);
		$this->EE->ttree->delete_branch($node['lft']);
		$this->EE->ttree->set_last_update_timestamp($tree_id);
		
		// altered our tree structure so rebuild our tree 
		// array column in exp_taxonomy_trees for this tree
		$data['tree_array'] = base64_encode(serialize( $this->EE->ttree->tree_to_array() ));
		$this->EE->db->where('id', $tree_id);
		$this->EE->db->update('exp_taxonomy_trees', $data); 
		
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('node_deleted'));
		$this->EE->functions->redirect($this->_base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
	}


	// ----------------------------------------------------------------


	/**
	 * Edit an existing tree, or add a new tree form
	 *
	 * @access	public
	 */
	function edit_nodes()
	{
		
		$tree_id = $this->EE->input->get('tree_id');
		
		$this->EE->ttree->check_tree_table_exists($tree_id, true);
		
		$tree_settings = $this->EE->ttree->get_tree_settings($tree_id);

		$root_array = $this->EE->ttree->get_root();
		
		// if we don't have a root redirect and prompt to enter one
		if( !$root_array)
		{
			$this->EE->functions->redirect($this->_base_url.AMP.'method=manage_node'.AMP.'tree_id='.$tree_id.AMP.'add_root=1');
		}
		
		$vars = array();
		$vars['root_insert'] = $this->EE->input->get('root_insert');
		$vars['tree_id'] = $tree_id;
		$vars['update_action'] = $this->_form_base_url.AMP.'method=reorder_nodes'.AMP.'tree_id='.$tree_id;
		$vars['last_updated'] = $tree_settings['last_updated'];
		$vars['title_extra'] = $tree_settings['label'];
		
		$tree_array = $this->EE->ttree->get_tree_array($tree_id);
		
		$vars['taxonomy_list'] = $this->EE->ttree->build_cp_list($tree_array);
		
		return $this->content_wrapper('edit_nodes', 'edit_nodes', $vars);

	}
	
	// ----------------------------------------------------------------


	/**
	 * Edit an existing tree, or add a new tree form
	 *
	 * @access	public
	 */
	function manage_node()
	{
	
		$tree_id = $this->EE->input->get('tree_id');
		
		$this->EE->ttree->check_tree_table_exists($tree_id, true);
		
		$this->EE->load->model('channel_entries_model');
				
		$vars = array();
		
		$vars['tree_settings'] 	= $this->EE->ttree->get_tree_settings($tree_id);
		
		// check this user has access to this tree
		$permissions = explode('|', $vars['tree_settings']['permissions']);
		if( !has_access_to_tree($this->EE->session->userdata['group_id'], $permissions) )
		{
			$this->EE->session->set_flashdata('message_failure', lang('unauthorised'));
			$this->EE->functions->redirect($this->_base_url);
		}
		
		$vars['title_extra'] = $vars['tree_settings']['label'];
		$vars['root_insert'] = $this->EE->input->get('add_root');
		$vars['channel_entries'] = array();
		$vars['templates'] = array();
		$vars['tree_id'] = $tree_id;
		$vars['parent_select'] = array();
		$vars['node_id'] = $this->EE->input->get('node_id');
		$vars['label'] = '';
		$vars['template_path'] = '';
		$vars['entry_id'] = '';
		$vars['custom_url'] = '';
		$vars['field_data'] = '';
		$vars['site_pages'] = $this->EE->config->item('site_pages');
		$vars['hide'] = " class='js_hide'";
		$vars['select_page_uri_option'] = '';
			
		// get template info from selected templates
		$template_ids = explode('|', $vars['tree_settings']['template_preferences']);
		$templates = $this->EE->ttree->get_templates($template_ids, $tree_id);
		
		// build our options for the template form_dropdown
		if( count($templates))
		{
			$vars['templates'][0] = '--';
			foreach($templates as $template)
			{
				// strip /index from each template
				$vars['templates'][ $template['template_id'] ] = str_replace('index/', '', '/'.$template['group_name'].'/'.$template['template_name'].'/');
			}
		}
		
		// get channel entries from selected templates
		$channel_ids  = explode('|', $vars['tree_settings']['channel_preferences']);
		$fields_needed = array("entry_id", "channel_id", "title");
		$channel_entries = $this->EE->channel_entries_model->get_entries($channel_ids, $fields_needed)->result_array();
		$channels = $this->EE->ttree->get_channels($this->site_id);
		
		// build an array of channel_ids => channel_titles
		foreach($channels as $channel)
		{
			$channel_names[$channel['channel_id']] = $channel['channel_title'];
		}
		// build our channel_entries form_dropdown array
		if( count($channel_entries))
		{
			$vars['channel_entries'][0] = '--';
			foreach($channel_entries as $entry)
			{
				$vars['channel_entries'][ $entry['entry_id'] ] = '['.$channel_names[$entry['channel_id']].'] &rarr; '.$entry['title'];
			}
		}

		// sort the entries alphabetically
		natcasesort($vars['channel_entries']);
		
		// build our nodes array for the select parent form_dropdown
		// only if we're not inserting a root
		if(!$vars['root_insert'] && !$vars['node_id'])
		{
			$flat_tree = $this->EE->ttree->get_flat_tree(1);
			foreach($flat_tree as $node)
			{
				$vars['parent_select'][$node['lft']] = str_repeat('-&nbsp;', $node['level']).$node['label'];
			}
		}

		// are we editing a node, get node's existing values
		if($vars['node_id'])
		{
			$this_node = $this->EE->ttree->get_node_by_node_id($vars['node_id']);
			
			if(count($this_node))
			{
				$vars['label'] = $this_node['label'];
				$vars['template_path'] = $this_node['template_path'];
				$vars['entry_id'] = $this_node['entry_id'];
				$vars['custom_url'] = $this_node['custom_url'];
				$vars['field_data'] = ($this_node['field_data']) ? $this->unserialize($this_node['field_data']) : '';
			}
			
			
		}
		
		// build options for 'use page uri' checkbox
		$checked = false;
						
		if(isset($this_node['custom_url']) && $this_node['custom_url'] == "[page_uri]")
		{
			//check it, and show it
			$checked = TRUE;
			$vars['hide'] = "";
		}
		
		$vars['site_pages_checkbox_options'] = array(
												    'name'        => 'use_page_uri',
												    'id'          => 'use_page_uri',
												    'value'       => '1',
												    'checked'     => $checked
											    );
		
		$vars['has_page_ajax_url'] = str_replace('&amp;','&', $this->_base_url.AMP."method=check_entry_has_pages_uri".AMP."tree_id=".$tree_id.AMP."node_entry_id=");
		
		// put the select entry field into a variable
		$entries_select_dropdown = form_dropdown('entry_id', $vars['channel_entries'], $vars['entry_id']);
		
		// fetch our existing entry_ids
		$entry_ids_in_tree = $this->EE->ttree->get_tree_entry_ids($tree_id);

		if($entry_ids_in_tree)
		{
			// loop through our entries and str_replace the disabled="disabled" in there :(
			foreach($entry_ids_in_tree as $row)
			{
				// don't want to disable our current node though :)
				if($row['entry_id'] != $vars['entry_id'])
				{
					$entries_select_dropdown = str_replace('value="'.$row['entry_id'].'"', 'value="'.$row['entry_id'].'" disabled="disabled"', $entries_select_dropdown);
				}
			}
		}
		
		$vars['entries_select_dropdown'] = $entries_select_dropdown;
		
		unset($entries_select_dropdown);

		return $this->content_wrapper('manage_node', 'manage_node', $vars);

	}
	
	
	// ----------------------------------------------------------------


	/**
	 * Edit an existing tree, or add a new tree form
	 *
	 * @access	public
	 */
	function update_node()
	{
		
		// @todo form validation properly
		$this->EE->load->library('form_validation');
		$this->EE->form_validation->set_rules('label', 'Label', 'required');
		
		if ($this->EE->form_validation->run('label') == FALSE)
		{
		   show_error('"Label" is a required field');
		}

		$tree_id 	= $this->EE->input->post('tree_id');
		$is_root	= $this->EE->input->post('is_root');
		
		$data = array();
		$data['label'] 		= htmlspecialchars($this->EE->input->post('label'), ENT_COMPAT, 'UTF-8');
		$data['template_path'] 	= $this->EE->input->post('template');
		$data['entry_id'] 	= $this->EE->input->post('entry_id');
		$data['node_id'] 	= $this->EE->input->post('node_id');
		$data['custom_url']	= $this->EE->input->post('custom_url');
		$data['field_data']	= ( is_array($this->EE->input->post('custom_fields')) ) ? base64_encode(serialize($this->EE->input->post('custom_fields'))) : '';
		
		$data = $this->EE->security->xss_clean($data);
		
		$this->EE->ttree->check_tree_table_exists($tree_id);
		
		// are we adding a root node
		if($is_root)
		{
			// returns true if root has been added
			if($this->EE->ttree->insert_root($data))
			{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('root_added'));
			}
			// we already have a root!
			else
			{
				$this->EE->session->set_flashdata('message_failure', lang('root_already_exists'));
			}
			
		}
		// are we updating
		elseif($data['node_id'])
		{
			$this->EE->ttree->update_node_by_node_id($data['node_id'], $data);
		}
		// we're inserting a new node
		else
		{
			$parent_lft = $this->EE->input->post('parent');
			$this->EE->ttree->append_node_last($parent_lft, $data);
		}
		
		unset($data);
		
		// insert our tree array
		$data['tree_array'] = base64_encode(serialize( $this->EE->ttree->tree_to_array() ));
		$data['last_updated'] = time();
		$this->EE->db->where('id', $tree_id);
		$this->EE->db->update('exp_taxonomy_trees', $data); 
		
		$this->EE->functions->redirect($this->_base_url.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
	
	}
	

	// ----------------------------------------------------------------
	
	
	
	/**
	 * Handles submit data from edit nodes ajax submission
	 */
	function reorder_nodes()
	{
		
		// @todo introduce some tests here to make sure data is a valid nested set etc.
		// completely reliant on clean outut from js here
		
		$tree_id = $this->EE->input->get_post('tree_id');

		$this->EE->ttree->check_tree_table_exists($tree_id);
		
		$tree_settings =  $this->EE->ttree->get_tree_settings($tree_id);
		
		$sent_last_updated = $this->EE->input->get_post('last_updated');

		if($sent_last_updated != $tree_settings['last_updated'])
		{
			$resp['data'] = 'last_update_mismatch';	
			$this->EE->output->send_ajax_response($resp);
		}

		$node_id = '';
		$lft = '';
		$rgt = '';

		$taxonomy_order = $this->EE->input->get_post('taxonomy_order');
		$taxonomy_order = rtrim($taxonomy_order, '|');

		if($taxonomy_order)
		{
			$m = explode("|", $taxonomy_order);
			
			$lq = "LOCK TABLE exp_taxonomy_tree_".$tree_id." WRITE";
			$res = $this->EE->db->query($lq);

			foreach($m as $items)
			{

				$item = explode(',', $items);
				
				if(isset($item[0]) && $item[0] != '')
				{
					$node_id 	= str_replace("id:", "", $item[0]);
					$lft		= str_replace("lft:", "", $item[1]);
					$rgt 		= str_replace("rgt:", "", $item[2]);
				}

            	if($node_id != 'root')
            	{
	            	 $data = array(
		               'node_id' 	=> $node_id,
		               'lft' 		=> $lft,
		               'rgt' 		=> $rgt
	            	);
	            	
	            	$this->EE->db->where('node_id', $node_id);
					$this->EE->db->update('exp_taxonomy_tree_'.$tree_id, $data);
	            	
	            }
	            
	            if($node_id == 'root')
            	{
	            	 $data = array(
		               'lft' 		=> $lft,
		               'rgt' 		=> $rgt
	            	);
	            	
	            	$this->EE->db->where('lft', $lft);
					$this->EE->db->update('exp_taxonomy_tree_'.$tree_id, $data);
	            	
	            }
				
			}
			
			$ulq = "UNLOCK TABLES";
			$res = $this->EE->db->query($ulq);
			
			
		}
		
		// update the last_updated timestamp
		$this->EE->ttree->set_last_update_timestamp($tree_id);
		
		// last_updated timestamp has been updated, so fetch again.
		unset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['settings']);
		$tree_settings =  $this->EE->ttree->get_tree_settings($tree_id);
		
		$resp['data'] = 'Node order updated';
		$resp['last_updated'] = $tree_settings['last_updated'];
		
		unset($data);
		
		// update our stashed tree array
		$this->EE->ttree->rebuild_tree_array($tree_id);
		$this->EE->output->send_ajax_response($resp);	

	}
	
	
	// ----------------------------------------------------------------
	
	
	/**
	 * Method for ajax requests when a user selects an entry for a node
	 * We check if the entry has a pages module uri and return it if it exists,
	 * otherwise we return false
	 *
	 * @access	public
	 */
	function check_entry_has_pages_uri()
	{

		$tree_id = $this->EE->input->get('tree_id');
		$this->EE->ttree->check_tree_table_exists($tree_id, true);
		$entry_id = $this->EE->input->get('node_entry_id');
		$response = FALSE;
		
		if($entry_id)
		{
			$response = $this->EE->ttree->entry_id_to_page_uri($entry_id, $this->site_id);

			if($response == "/404")
			{
				$response = FALSE;
			}
			
		}
		
		$resp['page_uri'] = $response;
				
		$this->EE->output->send_ajax_response($resp);							
									
	}
	
	
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

	 
	 
	function content_wrapper($content_view, $lang_key, $vars = array())
	{
		
		$vars['content_view'] = $content_view;
		$vars['_base_url'] = $this->_base_url;
		$vars['_form_base_url'] = $this->_form_base_url;
		$vars['_theme_base_url'] = $this->_theme_base_url;
		$title_extra = (isset($vars['title_extra'])) ? ': '.$vars['title_extra'] : '';

		$this->EE->cp->set_variable('cp_page_title', lang($lang_key).$title_extra);
		$this->EE->cp->set_breadcrumb($this->_base_url, lang('taxonomy_module_name'));

		return $this->EE->load->view('_wrapper', $vars, TRUE);
	}
	
}
/* End of file mcp.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/mcp.taxonomy.php */