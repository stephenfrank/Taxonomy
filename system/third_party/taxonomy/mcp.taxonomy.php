<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Build nested sets from channel entries
 *
 * @package		Taxonomy
 * @subpackage	ThirdParty
 * @category	Modules
 * @author		Iain Urquhart
 * @link		http://taxonomy-1.0:8888/
 */

class Taxonomy_mcp 
{
	var $base;			// the base url for this module			
	var $form_base;		// base url for forms
	var $module_name = "taxonomy";	

	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance(); 
		
		// define some vars
		$this->base	 	 	= BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->form_base 	= 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.$this->module_name;
		$this->theme_base 	= $this->EE->config->item('theme_folder_url').'third_party/taxonomy_assets/';
		$this->site_id	 	= $this->EE->config->item('site_id');
		
		// set the top nav
		$nav_array = array(
				'module_home'	=> $this->base,
				'add_tree'		=> $this->base.AMP.'method=add_tree');
        
        // remove the add_tree option for non super admins * @todo
        if($this->EE->session->userdata['group_id'] != 1)
        {
        	// unset($nav_array['add_tree']);
        }
		
		$this->EE->cp->set_right_nav($nav_array);
		
			
	}

	/**
	 * Module Home
	 */
	function index() 
	{

		$this->EE->load->library('table');
		$this->EE->load->helper('form');
		$this->_add_taxonomy_assets();

		$vars = array();
		
		// grab the trees
		$query = $this->EE->db->get_where('exp_taxonomy_trees',array('site_id' => $this->site_id));

		if($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
			
				$permissions = explode('|', $row['permissions']);
								
				$current_user_group_id = $this->EE->session->userdata['group_id'];
				
				// do we have parmissions to view this row/tree? bypass for superadmin
				if(is_array($permissions) && (in_array($current_user_group_id, $permissions) || ($this->EE->session->userdata['group_id'] == 1)))
				{
			
					$vars['trees'][$row['id']]['id'] = $row['id'];
					$vars['trees'][$row['id']]['site_id'] = $row['site_id'];
					$vars['trees'][$row['id']]['tree_label'] = $row['label'];
					$vars['trees'][$row['id']]['edit_tree_link'] = $this->base.AMP.'method=edit_tree'.AMP.'tree_id='.$row['id'];
					$vars['trees'][$row['id']]['edit_nodes_link'] = $this->base.AMP.'method=edit_nodes'.AMP.'tree_id='.$row['id'];
					$vars['trees'][$row['id']]['delete_tree_link'] = $this->base.AMP.'method=delete_tree'.AMP.'tree_id='.$row['id'];
				}
			}
		}
		else
		{
			// no trees exist, get started message
			return $this->content_wrapper('newbie', 'welcome', $vars);
		}

		return $this->content_wrapper('index', 'manage_trees', $vars);
	}

	/**
	 * Displays the Add Tree form
	 */
	function add_tree() 
	{

		$this->EE->load->helper(array('form', 'string', 'url'));
		$this->EE->load->library('table');
		$this->EE->load->library('MPTtree');
		$this->EE->load->model('tools_model');
		$this->_add_taxonomy_assets();

		$vars = array();

		// get the templates available
		$this->EE->load->model('template_model');
        $templates = $this->EE->template_model->get_templates($this->site_id);

        // got no templates?
        if ($templates->num_rows() == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_templates_exist'));
			$this->EE->functions->redirect($this->base);
		}
		
		
		
		foreach($templates->result_array() as $template)
		{
			$vars['templates'][$template['template_id']] = '/'.$template['group_name'].'/'.$template['template_name'].'/';
		}

		// get the channels available
		$channels = $this->EE->mpttree->get_channels($this->site_id);
		
		// no channels?	
		if ($channels->num_rows() == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_channels_exist'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy');
		}
		
		

		foreach($channels->result_array() as $channel)
		{
			$vars['channels'][$channel['channel_id']] = $channel['channel_title'];
		}
		
		// get the member groups available
		$this->EE->load->model('member_model');
		$member_groups = $this->EE->member_model->get_member_groups();
		
		
		
		$vars['member_groups'] = array();
		
		foreach($member_groups->result_array() as $member_group)
		{
			// only add to the array if the member group can access taxonomy 
			if( $this->EE->mpttree->can_access_taxonomy($member_group['group_id']) )
			{
				$vars['member_groups'][$member_group['group_id']] = $member_group['group_title'];
			}
		}

		return $this->content_wrapper('add_tree', 'add_tree', $vars);

	}
	
	
	
	/**
	 * Enter/update node tree data to exp_taxonomy_trees, 
	 * if new - create new tree table to hold nested set.
	 *
	 * @access	public
	 */
	function update_trees()
	{
		
		$template_preferences = "";
		$channel_preferences = "";
		$tree_id = $this->EE->input->post('id');
		$label = $this->EE->input->post('label');
		$fields = $this->EE->input->post('field');
		
		
		$tp_prefs_array = ($this->EE->input->post('template_preferences')) ? $this->EE->input->post('template_preferences') : "|";
		$cnl_prefs_array = ($this->EE->input->post('channel_preferences')) ? $this->EE->input->post('channel_preferences') : "|";
		$permissions = ($this->EE->input->post('member_group_preferences')) ? $this->EE->input->post('member_group_preferences') : "|";
		
		$new = ($tree_id != "") ? NULL : 1;
		
		if(is_array($tp_prefs_array))
		{
			$template_preferences .= implode('|', $tp_prefs_array);
		}
		
		if(is_array($cnl_prefs_array))
		{
			$channel_preferences .= implode('|', $cnl_prefs_array);
		}
		
		if(is_array($permissions))
		{
			$permissions = implode('|', $permissions);
		}
		
		$field_prefs = array();
		
		if(count($fields) && is_array($fields))
		{
			foreach($fields as $key => $field)
			{
				if($field['label'] && $field['name'])
				{
					$field_prefs[$key] = $field;
				}
			}
		}
		
		$field_prefs = (count($field_prefs) > 0) ? serialize($field_prefs) : '';
		
		$data = array(
						'id'					=> $tree_id,
						'site_id'				=> $this->site_id,
						'label'					=> $label,
						'template_preferences'	=> $template_preferences,
						'channel_preferences' 	=> $channel_preferences,
						'extra'					=> $field_prefs,
						'permissions'			=> $permissions
						);
		
		$data = $this->EE->security->xss_clean($data);
		
		// if we're creating a new tree, add the tree table.
		if($new)
		{
			
			$this->EE->db->query($this->EE->db->insert_string('exp_taxonomy_trees', $data));
			
			// unsure of how reliable this method is
			$last_tree_id = $this->EE->db->insert_id();
			
			// builds the taxonomy_tree_x table
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
					'extra'				=> array('type' => 'text')	
												
					);
				
			$this->EE->load->dbforge();
			$this->EE->dbforge->add_field($fields);
			$this->EE->dbforge->add_key('node_id', TRUE);
	
			$this->EE->dbforge->create_table('taxonomy_tree_'.$last_tree_id);
			
			unset($fields);
			
			$cp_message = $this->EE->lang->line('tree_added');
			
			$this->EE->session->set_flashdata('message_success', $cp_message);
			$this->EE->functions->redirect($this->base.AMP.'method=index');
			
		}
		// we're just updating
		else
		{
			$this->EE->db->query($this->EE->db->update_string('exp_taxonomy_trees', $data, "id = '$tree_id'"));
			$cp_message = $this->EE->lang->line('properties_updated');
			
			$this->EE->session->set_flashdata('message_success', $cp_message);
			$this->EE->functions->redirect($this->base.AMP.'method=edit_tree'.AMP.'tree_id='.$tree_id);
		}
	
	}


	/**
	 * Displays the Edit Tree form
	 */
	function edit_tree() 
	{
		$this->EE->cp->add_to_head('<link type="text/css" href="'.$this->theme_base.'css/taxonomy.css" rel="stylesheet" />');
		$this->EE->load->helper(array('form'));
		$this->EE->load->library('table');
		$this->EE->load->library('MPTtree');
		
		$tree_id = $this->EE->input->get('tree_id');
		
		$vars = array();
		
		// fetch the trees
		$this->EE->db->where_in('id', $tree_id);
		$query = $this->EE->db->get('taxonomy_trees');
		
		// no trees
		if ($query->num_rows() == 0)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('invalid_trees'));
			$this->EE->functions->redirect($this->base);				
		}
		
		foreach ($query->result() as $row)
		{
			$vars['tree_info']['id'] = $row->id;
			$vars['tree_info']['site_id'] = $row->site_id;
			$vars['tree_info']['label'] = $row->label;
			$vars['tree_info']['template_preferences'] = $row->template_preferences;
			$vars['tree_info']['channel_preferences'] = $row->channel_preferences;
			$vars['tree_info']['extra'] = ($row->extra != '') ? unserialize($row->extra) : '';
			$vars['tree_info']['permissions'] = $row->permissions;
		}
		
		if(is_array($vars['tree_info']['extra']))
		{
			$vars['tree_info']['extra'] = $this->EE->mpttree->array_sort($vars['tree_info']['extra'], 'order', SORT_ASC);
		}
		
		// get all templates
		$this->EE->load->model('template_model');
		$templates = $this->EE->template_model->get_templates($this->site_id);
		
		foreach($templates->result_array() as $template)
		{
			$vars['templates'][$template['template_id']] = '/'.$template['group_name'].'/'.$template['template_name'].'/';
		}

		// get all channels
		$channels = $this->EE->mpttree->get_channels($this->site_id);
		foreach($channels->result_array() as $channel)
		{
			$vars['channels'][$channel['channel_id']] = $channel['channel_title'];
		}
		
		// get the member groups available
		$this->EE->load->model('member_model');
		$member_groups = $this->EE->member_model->get_member_groups();
		
		$vars['member_groups'] = array();
		
		foreach($member_groups->result_array() as $member_group)
		{
			// only add to the array if the member group can access taxonomy 
			if( $this->EE->mpttree->can_access_taxonomy($member_group['group_id']) )
			{
				$vars['member_groups'][$member_group['group_id']] = $member_group['group_title'];
			}
		}
		
		return $this->content_wrapper('edit_tree', 'edit_tree', $vars);
	
	}

	/**
	 * Displays the Edit Node, Add New Node, and Add Root Node forms
	 */
	function manage_node()
	{
	
		$tree_id = $this->EE->input->get('tree_id');
		$this->validate_and_initialise_tree($tree_id);
		$this->EE->load->library('table');
		
		$this->EE->load->model('template_model');
		$this->EE->load->model('channel_entries_model');
		
		// add our css/js
		$this->_add_taxonomy_assets();
		
		// fetch the tree attributes (templates/channels etc)
		$tree_settings = $this->get_tree_settings($tree_id);
		
		$vars = array();
		$selected_templates_where = '';
		$tree_label = $tree_settings['label'];
		$templates = array();
		$channels = array();
		$entries_already_in_tree = array();
		$vars['current_node'] = array();
		$vars['select_page_uri_option'] = '';
		$vars['select_parent_dropdown'] = '';
		$vars['current_node']['lft'] = (isset($vars['current_node']['lft'])) ? $vars['current_node']['lft'] : '';
		$vars['tree_id'] = $tree_id;
		$node_id = ($this->EE->input->get('node_id')) ? $this->EE->input->get('node_id') : NULL;
		$vars['title_extra'] = $tree_settings['label'];

		$templates['selected']		= explode('|', $tree_settings['template_preferences']);
		$selected_channels			= explode('|', $tree_settings['channel_preferences']);

		$selected_templates_where 	= array("template_id" => $templates['selected']);
		$vars['templates'] = $this->generate_template_select_array($selected_templates_where);
		$channels = $this->EE->mpttree->get_channels($this->site_id);
		$site_pages = $this->EE->config->item('site_pages');
		
		// are we editing a node? if so fetch the node values
		if($node_id)
		{
			$vars['current_node'] = $this->EE->mpttree->get_node_by_nodeid($node_id);
		}
		
		// get the channel names too so we can prefix titles with channel names
		foreach($channels->result_array() as $channel)
		{
			$channel_title[$channel['channel_id']] = $channel['channel_title'];
		}

		$fields_needed = array("entry_id", "channel_id", "title");

		// might need to roll my own fetch entries function as 'get_entries' checks user access permissions to a channel
		// @todo
		$channel_entries = $this->EE->channel_entries_model->get_entries($selected_channels, $fields_needed);

		// default/null value
		$entries[0] = '--';

		// loop through and build our entry options
		foreach($channel_entries->result_array() as $entry)
		{
			$entries[$entry['entry_id']] = '['.$channel_title[$entry['channel_id']].'] &rarr; '.$entry['title'];
		}

		// sort the entries alphabetically
		natcasesort($entries);

		// build our select parent dropdown
		$flat_tree = $this->EE->mpttree->get_flat_tree(1);
		
		if($flat_tree)
		{
			$select_parent = $this->generate_select_parent_options($flat_tree);
		}
		
		// are we adding a node
		if($this->EE->input->get('add_root') != '1' && $vars['current_node']['lft'] != '1' && $node_id == '')
		{
			$vars['select_parent_dropdown'] = $select_parent;
		}
		// are we editing a node
		elseif(isset($vars['current_node']['lft']) && $vars['current_node']['lft'] == '1' || $node_id != '')
		{
			$vars['root'] = NULL;
		}
		// we're adding a root
		else
		{
			$vars['root'] = 'none';
		}
		
		// build our select entry dropdown
		$selected_entry_id = (isset($vars['current_node']['entry_id'])) ? $vars['current_node']['entry_id'] : '';
		$vars['select_entry_dropdown'] = $this->generate_select_entry_options($entries, $selected_entry_id);

		// build our 'use pages module uri' checkbox option
		// we'll hide the checkbox for now
		$hide = " class='js_hide'";
		
		
		// if pages exist
		if(isset($site_pages[$this->site_id]))
		{	
			$checked = FALSE;
			
			// does custom_url have a value, and is it [page_uri]
			if(isset($vars['current_node']['custom_url']) && $vars['current_node']['custom_url'] == "[page_uri]")
			{
				//check it, and show it
				$checked = TRUE;
				$hide = "";
			}
			// maybe it's not checked, but the selected entry does have a pages uri
			elseif(array_key_exists($selected_entry_id, $site_pages[$this->site_id]['uris']))
			{
				$hide = "";
			}
		
			$site_pages_checkbox_options = array(
			    'name'        => 'use_page_uri',
			    'id'          => 'use_page_uri',
			    'value'       => '1',
			    'checked'     => $checked
		    );
		    
		    $vars['select_page_uri_option'] = "<div id='taxonomy_use_page_uri'><div".$hide.">".form_checkbox($site_pages_checkbox_options)." ".lang('use_pages_module_uri')."</div></div>";
		
		}
		
		// custom fields?
		if(isset($tree_settings['extra']))
		{
			$vars['custom_fields'] = $this->EE->mpttree->array_sort(unserialize($tree_settings['extra']), 'order', SORT_ASC);
		}

		return $this->content_wrapper('manage_node', 'manage_node', $vars);
	
	}
	
	
	
	
	
	
	function edit_nodes() 
	{
	
		$tree_id = $this->EE->input->get('tree_id');
		$this->validate_and_initialise_tree($tree_id);

		$tree_settings = $this->get_tree_settings($tree_id);
		
		// print_r($tree_settings);
		
		$vars['tree_id'] = $tree_id;
		$vars['update_action'] = $this->form_base.AMP.'method=reorder_nodes'.AMP.'tree_id='.$tree_id;
		$vars['theme_base'] = $this->theme_base;	
		$vars['title_extra'] = $tree_settings['label'];

		// add our css/js
		$this->_add_taxonomy_assets();

		$root_array = $this->EE->mpttree->get_root();
		
		// root doesn't exist, so stop the user here and have them enter one.
		if($root_array === false)
		{
			$this->EE->functions->redirect($this->base.AMP.'method=manage_node'.AMP.'tree_id='.$tree_id.AMP.'add_root=1');
		}

		// get our tree into a workable array
		$tree_array = $this->EE->mpttree->tree2array_v2();
		
		// convert the array into the ordered list for the cp
		$vars['taxonomy_list'] = $this->EE->mpttree->mptree_cp_list($tree_array);
		
		// pass in the last updated timestamp
		$vars['last_updated'] = $tree_settings['last_updated'];
	
		return $this->content_wrapper('edit_nodes', 'edit_tree', $vars);
	}
	
	
	/**
	 * Handles submit data from edit nodes ajax submission
	 */
	function reorder_nodes()
	{

		$tree_id = $this->EE->input->get_post('tree_id');

		$this->validate_and_initialise_tree($tree_id);
		
		$tree_settings =  $this->get_tree_settings($tree_id);
		
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
		$this->set_last_update_timestamp($tree_id);
		
		// last_updated timestamp has been updated, so fetch again.
		unset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['settings']);
		$tree_settings =  $this->get_tree_settings($tree_id);
		
		$resp['data'] = 'Node order updated';
		$resp['last_updated'] = $tree_settings['last_updated'];
				
		$this->EE->output->send_ajax_response($resp);	

	}
	
	
	/**
	 * Add a node node form processor
	 */
	function process_manage_node()
	{
	
		$tree_id = $this->EE->input->post('tree_id');
		$node_id = $this->EE->input->post('node_id');
		$is_root = $this->EE->input->post('is_root');
		$extra 	 = $this->EE->input->post('extra');
		
		$this->validate_and_initialise_tree($tree_id);
		
		$parent_node_id = $this->EE->input->post('parent_node_node_id');
		
		$label = htmlspecialchars($this->EE->input->post('label'), ENT_COMPAT, 'UTF-8');

		if($extra)
		{
			$extra = $this->EE->security->xss_clean($extra);
			$extra = serialize($extra);
		}
		
		$data = array(
						'node_id'			=> $node_id,
						'label'				=> $label,
						'entry_id'			=> $this->EE->input->post('entry_id'),
						'template_path'		=> $this->EE->input->post('template_path'),
						'custom_url'		=> $this->EE->input->post('custom_url'),
						'extra'				=> $extra
						);
						
		$data = $this->EE->security->xss_clean($data);				

		if($is_root)
		{
			$this->set_last_update_timestamp($tree_id);
			$this->EE->mpttree->insert_root($data);
			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('root_added'));
			$this->EE->functions->redirect($this->base.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
		}
		elseif($node_id)
		{
			$this->EE->db->where('node_id', $node_id);
			$this->EE->db->update('exp_taxonomy_tree_'.$tree_id, $data);
		}
		else
		{
			$parent_node = $this->EE->mpttree->get_node_by_nodeid($parent_node_id);
			$this->EE->mpttree->append_node_last($parent_node['lft'],$data);
		}
		
		$this->set_last_update_timestamp($tree_id);
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('node_added'));
		$this->EE->functions->redirect($this->base.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id.AMP.time());

	}
	
	
	// delete a single node, except the root...
	function delete_node()
	{
		
		$tree_id = $this->EE->input->get('tree_id');
		$node_id = $this->EE->input->get('node_id');
		$type = $this->EE->input->get('type');
		
		$this->validate_and_initialise_tree($tree_id);
										
		$node = $this->EE->mpttree->get_node_byid($node_id);
		
		$this->EE->mpttree->delete_node($node['lft']);
		
		$this->set_last_update_timestamp($tree_id);
		
		if($type == 'ajax')
		{
			$resp['data'] = $this->generate_add_node_form();
			$resp['data'] .= $this->generate_edit_table();
			$this->EE->output->send_ajax_response($resp);
		}
		else
		{
			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('node_deleted'));
			$this->EE->functions->redirect($this->base.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
		}

	}
	
	
	// delete an entire branch (combine with above function when not 2am).
	function delete_branch()
	{
		$tree_id = $this->EE->input->get('tree_id');
		$node_id = $this->EE->input->get('node_id');
		$type = $this->EE->input->get('type');
		
		$this->validate_and_initialise_tree($tree_id);

		$node = $this->EE->mpttree->get_node_byid($node_id);
		$this->EE->mpttree->delete_branch($node['lft']);
		
		$this->set_last_update_timestamp($tree_id);

		if($type == 'ajax')
		{
			$resp['data'] = $this->generate_add_node_form();
			$resp['data'] .= $this->generate_edit_table();		
			$this->EE->output->send_ajax_response($resp);
		}
		else
		{
			$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('branch_deleted'));
			$this->EE->functions->redirect($this->base.AMP.'method=edit_nodes'.AMP.'tree_id='.$tree_id);
		}
		
	}
	
	
	/**
	 * Nuke the trees
	 *
	 * @access	public
	 */
	function delete_tree()
	{
		
		$tree_id = $this->EE->input->get('tree_id');
		$this->validate_and_initialise_tree($tree_id);
		
		$this->EE->load->dbforge();
		
		$this->EE->db->or_where('id', $tree_id);
		$this->EE->db->delete('exp_taxonomy_trees');
		$this->EE->dbforge->drop_table('taxonomy_tree_'.$tree_id);
	
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('tree_deleted'));
		$this->EE->functions->redirect($this->base);
		
	}
	
	
	
	
	
	function content_wrapper($content_view, $lang_key, $vars = array())
	{
		
		$vars['content_view'] = $content_view;
		$vars['_base'] = $this->base;
		$vars['_form_base'] = $this->form_base;
		$title_extra = (isset($vars['title_extra'])) ? ': '.$vars['title_extra'] : '';

		$this->EE->cp->set_variable('cp_page_title', lang($lang_key).$title_extra);
		$this->EE->cp->set_breadcrumb($this->base, lang('taxonomy_module_name'));

		return $this->EE->load->view('_wrapper', $vars, TRUE);
	}
	
	
	
	/**
	 * adds Taxonomy css and js to the head of the cp
	 */
	private function _add_taxonomy_assets()
	{
		$this->EE->cp->add_to_head('<link type="text/css" href="'.$this->theme_base.'css/taxonomy.css" rel="stylesheet" />');
		
		$site_pages = $this->EE->config->item('site_pages');
		$tree_id = $this->EE->input->get('tree_id');
		
		$url = $this->base.AMP."method=check_entry_has_pages_uri".AMP."tree=".$tree_id.AMP."node_entry_id=";
		
		$url = str_replace('&amp;','&',$url); 
		
		$extra_js = "<script type='text/javascript'>";
		
		if($site_pages)
		{
			$extra_js .= "jQuery.fn.detectPageURI = function(){
			
					//$.fancybox.showActivity();
					
					var url = '".$url."';
				    var node_entry_id = $(this).val();
				    var ajax_url = url+node_entry_id;
	
				    $.getJSON(ajax_url, function(data) {
				    	
				    	if(data.page_uri != null && data.page_uri != false){
				    		// alert('has page uri!');
				    		$('#taxonomy_use_page_uri div').fadeIn();
				    	}
				    	else
				    	{
				    		//alert('no page uri!');
				    		$('#taxonomy_use_page_uri div').fadeOut();
				    		$('#taxonomy_use_page_uri input').attr('checked', false);
				    		$('#taxonomy_select_template').show();
				    		
		       				var custom_url = $('#custom_url').val();
	    
						    if(custom_url == '[page_uri]')
						    {
						    	$('#custom_url').show().val('');
						    }
				    	}
	
					  //$.fancybox.hideActivity();
					 
					  
					});
				}";
		}
		else
		{
			$extra_js .= "jQuery.fn.detectPageURI = function(){}";
		}
		
		$extra_js .= "</script>";
		
		$this->EE->cp->add_to_head($extra_js);
		$this->EE->cp->add_to_head('<script type="text/javascript" src="'.$this->theme_base.'js/taxonomy.js"></script>');

		
	}
	

	/*
	 * receives a page entry id and returns true json response if the entry has a page_uri
	 */
	function check_entry_has_pages_uri()
	{

		$tree = $this->EE->input->get('tree');
		$this->validate_and_initialise_tree($tree);	
		$entry_id = $this->EE->input->get('node_entry_id');
		
		if($entry_id)
		{
			$site_id 		= $this->site_id;
			$node_uri 		= $this->EE->mpttree->entry_id_to_page_uri($entry_id, $site_id);

			$response = $node_uri;
			
			if($node_uri == "/404")
			{
				$response = FALSE;
			}
			
		}
		else
		{
			$response = FALSE;
		}
		
		$resp['page_uri'] = $response;
				
		$this->EE->output->send_ajax_response($resp);							
									
	}
	
	
	/*
	 * validate tree exists, checks user permissions
	 * set opts on mpttree
	 */ 
	private function validate_and_initialise_tree($tree_id = NULL)
	{
		// check the tree is being passed
		if ( ! $tree_id)
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_such_tree'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy');
		}
		
		// check the tree table exists
		if ($this->EE->db->table_exists('exp_taxonomy_tree_'.$tree_id))
		{
			
			$this->get_tree_settings($tree_id);
			
			$permissions = ( isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['settings']['permissions']) ) ? 
							explode('|', $this->EE->session->cache['taxonomy']['tree'][$tree_id]['settings']['permissions']) : '';
			
			if(is_array($permissions) && !in_array($this->EE->session->userdata['group_id'], $permissions) && ($this->EE->session->userdata['group_id'] != 1))
		    {
		    	$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('unauthorized'));
				$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy');
		    }

		}
		else
		{
			$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_such_tree'));
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy');
		}
		
		// all good, load the library
		$this->EE->load->library('MPTtree');
		$this->EE->mpttree->set_opts(array( 'table' => 'exp_taxonomy_tree_'.$tree_id,
										'left' => 'lft',
										'right' => 'rgt',
										'id' => 'node_id',
										'title' => 'label'));

	}
	

	/*
	 * sets the last_updated timestamp for a tree
	 * required as nested set can get real screwed if multiple folks are editing
	 */ 
	private function set_last_update_timestamp($tree_id)
	{
		$id = (isset($tree_id)) ? $tree_id : 0;
    	$time = time();
		$data = array('last_updated' => $time);
		$this->EE->db->where('id', $id);
		$this->EE->db->update('exp_taxonomy_trees', $data);
	}
	
	/*
	 * returns a tree's properties/preferences
	 */ 
	private function get_tree_settings($tree_id)
	{
		$id = (isset($tree_id)) ? $tree_id : 0;
		
		if($id ==0)
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
				$tree_info['extra'] = $row->extra;
				$tree_info['permissions'] = $row->permissions;
			}
			
			$this->EE->session->cache['taxonomy']['tree'][$id]['settings'] = $tree_info;

		}
		
		return $this->EE->session->cache['taxonomy']['tree'][$id]['settings'];
		
	}
	
	// generates the select parent menu
	// $tree array generated from $this->EE->mpttree->get_flat_tree(1);
	private function generate_select_parent_options($tree = array())
	{

		$tree_id = $this->EE->input->get('tree_id');
		$entries_already_in_tree = array();
		
		$r = "<select name='parent_node_node_id'>\n";

		// loop through our tree, 
		// and we might as well build the array of $entries_already_in_tree while we're at it
		foreach ($tree as $node)
		{
			// create an array of channel entries that exist in the tree
			if($node['entry_id'])
			{
				$entries_already_in_tree[] = $node['entry_id'];
			}
			// build the option
			$r .= "<option value='".$node['node_id']."'>".str_repeat('-&nbsp;', $node['level']).$node['label']."</option>\n";

		}
		
		// store the selected entries in the users session
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['selected_entry_ids']))
		{
			$this->EE->session->cache['taxonomy']['tree'][$tree_id]['selected_entry_ids'] = $entries_already_in_tree;
		}

		$r .= "</select>\n";
		
		return $r;
	
	}
	
	private function generate_template_select_array($selected_templates_where)
	{
		// grab the templates
        $tquery = $this->EE->template_model->get_templates($this->site_id, array(), $selected_templates_where);

		// give our form dropdown a null value
		$templates['options'][0] = '--';
		
		// Build our 'select a template' form dropdown array
		// remove /index label from each template group
		foreach($tquery->result_array() as $template)
		{
			if($template['template_name'] != 'index')
			{
				$templates['options'][$template['template_id']] = '/'.$template['group_name'].'/'.$template['template_name'].'/';
			}
			else
			{
				$templates['options'][$template['template_id']] = '/'.$template['group_name'].'/';
			}
		}
		
		if( count($templates['options']) == 1)
		{
			return array();
		}
		
		return $templates['options'];
	}
	
	// build our select entry options manually because
	// we want existing entry option already in the tree to be disabled
	// multiple nodes with the same entry id in one tree == barney rubble
	private function generate_select_entry_options($entries, $selected_entry_id = '')
	{
		
		
		if( count($entries) == 1)
		{
			return '';
		}
		
		$tree_id = $this->EE->input->get('tree_id');
		$entries_already_in_tree = array();
		

		if (isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['selected_entry_ids']))
		{
			$entries_already_in_tree = $this->EE->session->cache['taxonomy']['tree'][$tree_id]['selected_entry_ids'];
		}
		
		$r = "<div class='taxonomy-select-entry'>\n";
		$r .= "<select name='entry_id'>\n";
		foreach($entries as $entry_id => $entry_label)
		{

			$option_selected = NULL;
			$option_disabled = NULL;

			// echo $entry_id."==".$selected['entry_id']."<br />";

			// add selected to selected entry
			if($entry_id == $selected_entry_id)
			{
				$option_selected = " selected = 'selected'";
			}

			// disable the options for entries that exist in the tree, 
			// and isn't the already selected entry for the current node
			if(((in_array($entry_id, $entries_already_in_tree)) && $entry_id != $selected_entry_id) && $entry_id != '')
			{
				$option_disabled = " disabled = 'disabled'";
			}

			$r .= "<option value='".$entry_id."'".$option_selected.$option_disabled.">".$entry_label."</option>\n";

		}
		
		$r .= "</select>\n";
		$r .= "</div>\n";	
		
		return $r;

	}
	
	
	
	
	
	
}

/* End of file mcp.taxonomy.php */ 
/* Location: ./system/expressionengine/third_party/taxonomy/mcp.taxonomy.php */ 