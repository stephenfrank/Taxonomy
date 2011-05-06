<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Taxonomy_ft extends EE_Fieldtype
{
	public $info = array(
		'name' => 'Taxonomy',
		'version' => '2.0.0'
	);
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * constructor
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		parent::EE_Fieldtype();
		$this->site_id = $this->EE->config->item('site_id');
		$this->cache =& $this->EE->session->cache['taxonomy_ft_data'];
	}
	
	
	// --------------------------------------------------------------------
	
	
	public function install()
	{
		return array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * display_field
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_field($data)
	{

		$vars = array();
		$this->EE->lang->loadfile('taxonomy');
		
		// Note: $data array of node fields is returned if the user tries to save the entry
		// and publish validation fails, eg required field missing
		// we don't store anything in the actual field, but this is why $data is
		// referenced below.
		
		// make sure the field been configured
		if( !isset($this->settings['options']) OR !$this->settings['options'] )
			return "<p>".lang('field_not_configured')."</p>";
		
		// fetch the settings
		$settings = $this->unserialize($this->settings['options']);
		
		$channel_id = $this->EE->input->get('channel_id');
		
		// make sure this channel/field is associated to a tree
		if( !in_array($channel_id, $settings['channel_id']))
			return "<p>".lang('field_not_configured_for_this_channel')."</p>";

		foreach($settings['channel_id'] as $key => $val)
		{
			if($val === $channel_id)
			{
				$tree_key = $key;
			}
		}
				
		$vars['tree_id'] = (int) $settings['tree_id'][$tree_key];
		$vars['enable_pages_mode'] = (int) $settings['enable_pages_mode'][$tree_key];
		$vars['hide_template_select'] = (int) $settings['hide_template_select'][$tree_key];
		
		$this->EE->load->library('table');
		$this->EE->load->library('Ttree');
		
		$this->EE->load->helper('taxonomy');

		if(!$this->EE->ttree->check_tree_table_exists($vars['tree_id']) )
			return "<p>".lang('no_such_tree')."</p>";

		$vars['tree_settings'] = $this->EE->ttree->get_tree_settings($vars['tree_id']); // print_r($vars['tree_settings']);
		$vars['parent'] = ( isset($data['parent']) ) ? $data['parent'] : 0;
		$vars['template_path'] = ( isset($data['template']) ) ? $data['template'] : 0;
		$vars['field_name'] = $this->field_name;
		$vars['field_id'] = $this->field_id;
		$vars['label'] = ( isset($data['label']) ) ? $data['label'] : '';
		$vars['node_id'] = null;
		$vars['is_root'] = 0;
		$vars['fields_data'] = '';
		$vars['custom_url'] = '';
		
		
		$this->_add_taxonomy_css();
		
		// build our nodes array for the select parent form_dropdown
		$flat_tree = $this->EE->ttree->get_flat_tree();
		
		// if we don't have any nodes, stop here
		if( !$flat_tree )
			return "</p>No nodes exist, please enter a root node</p>";
		
		foreach($flat_tree as $node)
		{
			$vars['parent_select'][$node['node_id']] = str_repeat('-&nbsp;', $node['level']).$node['label'];
		}
		
		// load the actual dropdown into a var as we want to find/replace within it to disable this node if we're editing
		$vars['select_parent_dropdown'] = form_dropdown($this->field_name.'[parent]', $vars['parent_select'], $vars['parent']);

		// get template info from this trees selected templates
		$template_ids = explode('|', $vars['tree_settings']['template_preferences']);
		
		$templates = $this->EE->ttree->get_templates($template_ids, $vars['tree_id']);
		
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
		
		// are we editing a node? Fetch it's values as long as $data isn't 
		// coming back as an array from a publish validation error
		if($this->EE->input->get('entry_id') && !is_array($data))
		{
			$entry_id = $this->EE->input->get('entry_id');
			// fetch this nodes values
			$node_data = $this->EE->ttree->get_node_by_entry_id($entry_id);
			
			if($node_data)
			{
				$vars['node_id'] = $node_data['node_id'];
				$vars['label'] = $node_data['label'];
				$vars['template_path'] = $node_data['template_path'];
				// fetch parent values
				$parent_data =  $this->EE->ttree->get_parent($node_data['lft'], $node_data['rgt']);
				$vars['parent'] = $parent_data['node_id'];
				$vars['fields_data'] = $this->unserialize($node_data['field_data']);
				$vars['custom_url'] = $node_data['custom_url'];
			
				// do a find and replace to disable the current node from the select parent options
				// would screw the tree if a node is defining itself as it's own parent
				$vars['select_parent_dropdown'] = form_dropdown($this->field_name.'[parent]', $vars['parent_select'], $parent_data['node_id']);
				if($node_data['lft'] != 1)
				{
					$vars['select_parent_dropdown'] = str_replace('value="'.$node_data['node_id'].'"', 'value="'.$node_data['node_id'].'" disabled="disabled"', $vars['select_parent_dropdown']);
				}
				else
				{
					$vars['is_root'] = 1;
				}
			}
			
		}
		
		$vars['custom_fields'] = ($vars['tree_settings']['fields']) ? array_sort($vars['tree_settings']['fields'], 'order', SORT_ASC) : '';
		 
		
		return $this->EE->load->view('field', $vars, TRUE);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * save
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save($data)
	{
		// cache for post_save so we have access to the entry_id if it's a new entry
		$this->cache['data'][$this->settings['field_id']] = $data;
		return '';
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * post_save
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	function post_save($data)
	{
	
		$data = $this->cache['data'][$this->settings['field_id']];
		
		
		$this->EE->load->library('Ttree');
		
		if(!$data OR !$data['label'] OR !$this->EE->ttree->check_tree_table_exists($data['tree_id']))
			return '';
			
		$parent_node = (isset($data['parent']) ) ? $this->EE->ttree->get_node_by_node_id($data['parent']) : '';
		
		$custom_field_data = base64_encode( serialize( $this->EE->input->post('custom') ) );


		// get our array together for the insert/update
		$taxonomy_data = array(
						'node_id'			=> $data['node_id'],
						'label'				=> htmlspecialchars($data['label'], ENT_COMPAT, 'UTF-8'),
						'entry_id'			=> $this->settings['entry_id'],
						'template_path'		=> (isset($data['template']) ? $data['template'] : NULL),
						'custom_url'		=> (isset($data['use_page_uri']) ? '[page_uri]' : $data['custom_url']),
						'field_data'		=> $custom_field_data
						);

		// no node_id, insert new one
		if($data['node_id'] == '')
		{
			unset($data['node_id']);
			
			$this->EE->ttree->append_node_last($parent_node['lft'], $taxonomy_data);
		}
		// we have node_id, update node
		else
		{
			// we have to see if the parent node has changed
			$org_parent = $data['org_parent'];
			$this_parent = ( isset($data['parent']) ) ? $data['parent'] : '';
			$this_node = $this->EE->ttree->get_node_by_node_id($data['node_id']);
			
			// has changed and it's not the root!
			if($this_parent != $org_parent && $data['is_root'] != 1)
			{	
				// delete the node and rebuild the tree lfts and rgts
				$this->EE->ttree->delete_node($this_node['lft']);
				
				// now the lft values have possibly changed by the above delete
				// find the intended parent node by its node_id, as that hasn't changed
				$new_parent = $this->EE->ttree->get_node_by_node_id($this_parent);
				
				// re-insert with correct parent lft value
				$this->EE->ttree->append_node_last($new_parent['lft'], $taxonomy_data);
			}
			else
			{
				$this->EE->ttree->update_node($this_node['lft'], $taxonomy_data);
			}
			
		}

		$this->EE->ttree->set_last_update_timestamp($data['tree_id']);
		
		$this->EE->ttree->rebuild_tree_array($data['tree_id']);
		
		return '';
	
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * pre_process
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function pre_process($data)
	{
		return $data;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * replace_tag
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @param	mixed $params = array()
	 * @param	mixed $tagdata = FALSE
	 * @return	void
	 */
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return NULL;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * display_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function display_settings($data)
	{
		$this->EE->load->library('Ttree');
		$this->EE->lang->loadfile('taxonomy');
		$this->_add_taxonomy_css();
		$vars = array();
		
		if(isset($data['options']) && $data['options'])
		{
			$vars['data'] = $this->unserialize($data['options']);
		}
		
		// fetch the trees available on this site
		$query = $this->EE->db->get_where('exp_taxonomy_trees',array('site_id' => $this->EE->config->item('site_id')));
		
		$channels = $this->EE->ttree->get_channels($this->site_id);
		
		$this_field_group = $this->EE->input->get('group_id');
		
		// we only show channels which use this field group
		// unset channel options where field_group != this field's group
		foreach($channels as $key => $channel)
		{
			if($channel['field_group'] != $this_field_group)
			{
				unset($channels[$key]);
			}
		}
		
		// print_r($channels);
		
		//build the select options
		$tree_options = array();
		
		// give the options for which tree to associate with this field
		foreach($query->result_array() as $row)
		{
			$vars['tree_options'][$row['id']] = $row['label'];
		}
		
		foreach( $channels as $channel )
		{
			$vars['channels'][$channel['channel_id']] = $channel['channel_title'];
		}

		$this->EE->table->add_row(
			array('data' => $this->EE->load->view('field_settings', $vars, TRUE), 'colspan' => 2)				
		);
			
	}
		
		
	// --------------------------------------------------------------------
 		
 		
 	/**
	 * display_settings
	 * 
	 * @access	public
	 * @param	mixed $data
	 * @return	void
	 */
	public function save_settings($data)
	{
		$options = $this->EE->input->post('options');
		
//		print_r($options);
		
		if(is_array($options))
		{
			// remove our template options
			unset($options['channel_id'][0]);
			unset($options['tree_id'][0]);
			unset($options['enable_pages_mode'][0]);
			unset($options['hide_template_select'][0]);
			$options = $this->EE->security->xss_clean($options);
			$options = base64_encode(serialize($options));
		}
				
		return array(
			'options' => $options,
		);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Load taxonomy CSS
	 */
	private function _add_taxonomy_css()
	{
		if (! isset($this->EE->session->cache['taxonomy']['css_added']) )
		{
			$this->EE->session->cache['taxonomy']['css_added'] = 1;
			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->EE->config->item('theme_folder_url').'third_party/taxonomy_assets/css/taxonomy.css'.'" />');
		}
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
	

}

/* End of file ft.taxonomy.php */
/* Location: ./system/expressionengine/third_party/taxonomy/ft.taxonomy.php */