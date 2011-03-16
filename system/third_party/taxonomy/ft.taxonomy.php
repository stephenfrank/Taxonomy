<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  Taxonomy Fieldtype for ExpressionEngine 2
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author    	Iain Urquhart <shout@iain.co.nz>
 * @copyright 	Copyright (c) 2010 Iain Urquhart
 * @license   	http://creativecommons.org/licenses/MIT/  MIT License
*/

	class Taxonomy_ft extends EE_Fieldtype
	{
		var $info = array(
			'name'		=> 'Taxonomy',
			'version'	=> '0.4'
		);

		function __construct()
		{
			parent::EE_Fieldtype();
			include_once PATH_THIRD.'taxonomy/libraries/MPTtree.php';
			$this->EE->lang->loadfile('taxonomy');
		}	

		public function display_field($data)
		{
			
			$mpttree = new MPTtree;

			$tree = $this->settings['tree_id'];
						
			// call the tree
			$mpttree->set_opts(array( 'table' => 'exp_taxonomy_tree_'.$tree,
										'left' => 'lft',
										'right' => 'rgt',
										'id' => 'node_id',
										'title' => 'label'));

			// check the tree table exists
			if (!$this->EE->db->table_exists('exp_taxonomy_tree_'.$tree))
			{
				return $this->EE->lang->line('no_such_tree');
			}

			$this->EE->db->where_in('id', $tree);
			$query = $this->EE->db->get('taxonomy_trees');

			// grab the Taxonomy preference values
			foreach ($query->result() as $row)
			{
				$usertemplates 	=  $row->template_preferences;
				$userchannels	=  $row->channel_preferences;
				$userfields		=  $row->extra;
			}

			if($usertemplates == 0)
			{
				$usertemplates = array();
			}
			else
			{
				$usertemplates = array("template_id" => explode('|',$usertemplates));
			}
			
			// options for pages mode
			$enable_pages_mode = FALSE;
			$hide_template_select = FALSE;
			$pages_option = NULL;
			$page_uri_exisits = NULL;

	
			// Get Templates
	        $this->EE->load->model('template_model');
	        $tquery = $this->EE->template_model->get_templates($this->EE->config->item('site_id'), array(), $usertemplates);

	        $templates = array();

	        // give a null value for template pulldown
			$templates['options'][0] = '--';

			// remove /index label from each template group
			foreach($tquery->result_array() as $template)
			{
				if($template['template_name'] =='index')
				{
					$templates['options'][$template['template_id']] = '/'.$template['group_name'].'/';
				}
				else
				{
					$templates['options'][$template['template_id']] = '/'.$template['group_name'].'/'.$template['template_name'].'/';
				}
			}

			$text_direction = ($this->settings['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr';

			// template dropdown
			
			$data['template'] = (isset($data['template'])) ? $data['template'] : '';
			
			$template = form_dropdown($this->field_name.'[template]', $templates['options'], $data['template'], 'dir="'.$text_direction.'" id="taxonomy_template_select_'.$this->field_id.'"' );

			// node label
			$label = form_input(array(
									'name'	=> $this->field_name.'[label]',
									'id' 	=>'taxonomy_label_'.$this->field_id,
									'value'	=> (isset($data['label'])) ? $data['label'] : ''
								));

			// fetch the nodes
			$taxonomy_nodes = $mpttree->get_flat_tree_v2();

			// are there nodes in this tree?
			if( ! $taxonomy_nodes)
			{
				// @todo
				$msg = lang('no_root_node');
				return '<p>'.$msg.'</p>';
			}
			
			// build the select parent pulldown
			$parent_node_options = "<select name='".$this->field_name."[parent_node_id]'>";
			$parent_node_options .= "<option value=''>--</option>";
			
			// if the form doesn't validate, grab the user's selected parent from $data not the db
			$data['parent_node_id'] = (isset($data['parent_node_id']) ? $data['parent_node_id'] : '');
			
			foreach ($taxonomy_nodes as $node)
			{
				// check for selection from $data
				$selected = '';
				if($data['parent_node_id'] == $node['node_id'])
				{
					$selected = " selected='selected'";
				}
			
				$parent_node_options .= "<option value='".$node['node_id']."'".$selected.">".str_repeat ('-&nbsp;', $node['level']) . $node['label']."</option>";
			}
			
			$parent_node_options .= "</select>";

			// we'll presume this is a new node for now.
			$submission_type = 'new';

			//output the field table
			$return = '';
			
			$breadcrumb = '';

			// are we editing an entry?
			if($this->EE->input->get('entry_id'))
			{

				$existing_entry = $this->EE->input->get('entry_id');

				// find if it exists in the tree already, and grab its values from taxonomy
				$this->EE->db->where_in('entry_id', $existing_entry);

				// we're making a presumption here that this entry only exists once in the tree (?!)
				$query = $this->EE->db->get('exp_taxonomy_tree_'.$tree, 1);

				// grab the Taxonomy values for this node
				foreach ($query->result() as $row)
				{
					// flag it as an edit for the save process
					$submission_type = 'edit';
					$breadcrumb = '';
					
					$label = form_input(array(
											'name'	=> $this->field_name.'[label]',
											'id'	=> 'taxonomy_label_'.$this->field_id,
											'value'	=> htmlspecialchars_decode($row->label)
										));

					// rebuilt the select parent entry select menu
					$parent_node_options = "<select name='".$this->field_name."[parent_node_id]'>";
					$parent_node_options .= "<option value=''>--</option>";

					$parent = $mpttree->get_parent($row->lft,$row->rgt);
					
					// build the path to here crumb
					$path = $mpttree->get_parents($row->lft,$row->rgt);
					
					// print_r($path);
					
					$path = array_reverse($path);
					$depth = 0;		
					foreach($path as $crumb)
					{
						$breadcrumb .= $crumb['label'].' &rarr; ';
						$depth++;
					}

					if($depth == 0)
					{
						$breadcrumb .= lang('this_is_root');
					}
					else
					{
						$breadcrumb .= $row->label;
					}

					foreach ($taxonomy_nodes as $node)
					{
						$selected = '';
						$disabled = '';
						
						// select the existing option
						if($node['lft'] == $parent['lft'])
						{
							$selected = " selected='selected'";
						}
						
						// disable the node itself so the user can't select itself as a parent
						if($node['lft'] == $row->lft)
						{
							// might have to use jquery here, IE6/7 horror.
							$disabled = " disabled='disabled'";
						}
						
						$parent_node_options .= "<option value='".$node['node_id']."'".$selected.$disabled.">".str_repeat ('-&nbsp;', $node['level']) . $node['label']."</option>";
					}

					$parent_node_options .= "</select>";

					// replace active/selected template option with selected attribute
					$template = str_replace('value="'.$row->template_path.'">', 'value="'.$row->template_path.'" selected="selected">', $template);
					

					// check for the value of custom url,
					// if it contains [page_uri]
					// set the checkbox for 'use page uri'
					if($row->custom_url == "[page_uri]")
					{
						$page_uri_exisits = TRUE;
					}
					
					if($row->custom_url && $row->custom_url != "[page_uri]")
					{
						$custom_url = $row->custom_url;
					}
					
					$fields_data = ($row->extra != '') ? unserialize($row->extra) : '';

				}

			}

			// add the hidden field that flags if this is 'new' or an 'edit' submission_type
			$return .= form_hidden($this->field_name.'[submission_type]', $submission_type, '');
			
			
			
			// if the settings for the field have enabled pages mode, 
			// & the admin has opted to keep the template picker available
			if(isset($this->settings['enable_pages_mode']) && !isset($this->settings['hide_template_select'])) 
			{
	
				$pages_mode_checkbox_options = array(
			    'name'        => $this->field_name.'[use_page_uri]',
			    'id'          => $this->field_name.'_use_page_uri',
			    'value'       => '1',
			    'checked'     => $page_uri_exisits
		    	);

				$pages_option = form_checkbox($pages_mode_checkbox_options).' Use Page URI';
				
				
				$return .= "
				
				<script type='text/javascript'>
				$(document).ready(function() {
				$('input#".$this->field_name."_use_page_uri').change(function () {
					    if ($(this).attr('checked')) {
					        //do the stuff that you would do when 'checked'
							// alert('checked');
							$('tr#taxonomy_template_select_row_".$this->field_id."').hide();
					        return;
					    }
					    //Here do the stuff you want to do when 'unchecked'
					    //alert('unchecked');
					    $('tr#taxonomy_template_select_row_".$this->field_id."').show();
					});
					
					$('input#".$this->field_name."_use_page_uri:checked').each( 
					    function() { 
					       	$('tr#taxonomy_template_select_row_".$this->field_id."').hide();
						} 
					);
				});
				</script>
				
				<style type='text/css'>
					
					.taxonomy_fetch_title { padding: 0 2px; color: green}
					.taxonomy_fetch_title:hover {cursor: pointer;}
					
				</style>
				
				";
				
			}
			
			$return .= "
			
				<script type='text/javascript'>
					// set the taxonomy label from the title
					$(document).ready(function() {
						$('.taxonomy_fetch_title').click(function() {
								var titleval = $('input#title').val();								
								$('#taxonomy_label_".$this->field_id."').val(titleval);
							});
						});
				</script>
				
				<style type='text/css'>
					
					.taxonomy_fetch_title { padding: 0 2px; font-weight: bold; background: #fff; padding: 0 3px; border-radius: 3px; -webkit-box-shadow: 0 1px 1px rgba(0,0,0, 0.2); margin-left: 3px;}
					.taxonomy_fetch_title:hover {cursor: pointer;}
					.taxonomy_table tr td { height: 23px;}
					.taxonomy_table tr td.taxonomy_crumb_holder {background: #FDFCD1;}
					
				</style>";

			// if we're hiding the template select, force taxonomy to insert the [page_uri]
			if(isset($this->settings['hide_template_select']))
			{
				$hide_template_select = TRUE;
				$return .= form_hidden($this->field_name.'[use_page_uri]', 1);
			}			

			$return .= '
					<table class="mainTable taxonomy_table" border="0" cellspacing="0" cellpadding="0" style="margin-top: 5px;">
					<thead>
							<tr>
								<th colspan="2">'.$this->EE->lang->line('node_properties').'</th>
							</tr>
					</thead>
					<tbody>';

			$return .= '	<tr>
								<td style="width: 140px;"><strong>'.$this->EE->lang->line('node_label').'</strong> <span class="taxonomy_fetch_title" title="'.$this->EE->lang->line('fetch_title').'">+</span></td>
								<td>'.$label.'</td>
							</tr>';
			
			// if we don't have a custom uri in the tree for this node
			// and it's not a pages module association
			
			
			$return .= '	<tr>
								<td><strong>'.$this->EE->lang->line('parent_node').':</strong></td>
								<td>'.$parent_node_options.' &nbsp; '.$pages_option.'</td>
							</tr>';

			if(isset($custom_url))
			{
			
				$custom_url_input = form_input(array(
									'name'	=> $this->field_name.'[override_url]',
									'id'	=> 'taxonomy_override_url_'.$this->field_id,
									'value'	=> $custom_url,
									'readonly' => 'readonly',
									'style' => 'opacity: 0.5;'
								));
			
				$return .= '	<tr>
								<td style="width: 140px;"><strong>'.$this->EE->lang->line('override_url').':</strong></td>
								<td>'.$custom_url_input.'</td>
							</tr>';
			
			}
			
			
			if(!isset($this->settings['hide_template_select']) && !isset($custom_url))
			{
				$return .= '<tr id="taxonomy_template_select_row_'.$this->field_id.'">
								<td><strong>'.$this->EE->lang->line('template').':</strong></td>
								<td>'.$template.'</td>
							</tr>';
			}
			
			$hidden_custom_fields = '';

			// custom fields
			if($userfields)
			{
				
				$userfields = $mpttree->array_sort(unserialize($userfields), 'order', SORT_ASC);
				
				
				foreach($userfields as $custom_field)
				{
					
					// does the array key exist, if so grab the value
					$value = (isset($fields_data[$custom_field['name']])) ? $fields_data[$custom_field['name']] : '';
					
					// output our custom fields
					if(isset($custom_field['show_on_publish']) && $custom_field['show_on_publish'] == 1)
					{

						switch($custom_field)
						{
							case($custom_field['type'] == 'text'):
									$custom_field_label = $custom_field['label'].':';
									$custom_field_input = form_input('extra['.$custom_field['name'].']', $value, 'id='.$custom_field['name']);
								break;
							case($custom_field['type'] == 'checkbox'):
									$custom_field_label = '&nbsp;';
									$custom_field_input = form_checkbox('extra['.$custom_field['name'].']', 1, $value).' &nbsp; '.$custom_field['label'];
								break;
							case($custom_field['type'] == 'textarea'):
									$custom_field_label = $custom_field['label'].':';
									$custom_field_input = form_textarea('extra['.$custom_field['name'].']', $value, 'id='.$custom_field['name'].',  style=" height:60px;"');
								break;
						}


						$return .= '<tr>
									<td><strong>'.$custom_field_label.'</strong></td>
									<td>'.$custom_field_input.'</td>
									</tr>';
					}
					
					else
					{
						$hidden_custom_fields .= form_hidden('extra['.$custom_field['name'].']', $value);
					}
				}
				
			}
			
			if($breadcrumb)
			{
				// $return .= '<tr><td>'.lang('path_to_here').'</td><td>'.$breadcrumb.'</td></tr>';
			}
			
			$return .= '</tbody></table>'. $hidden_custom_fields;
					
			
			return $return;

		}
		
		
		public function replace_tag($data, $params = FALSE, $tagdata = FALSE)
		{

		}
		
		public function save($data)
		{
				//print_r($data);
				$this->cache['data'][$this->settings['field_id']] = $data;
		}
		
		function post_save($data)
		{

			$data = $this->cache['data'][$this->settings['field_id']];
			
			if(!$data)
			{
				return NULL;
			}
			
			$mpttree = new MPTtree;
			
			// bit hacky @todo
			$tree = $this->settings['tree_id'];
			
			// call the tree
			$mpttree->set_opts(array( 'table' => 'exp_taxonomy_tree_'.$tree,
										'left' => 'lft',
										'right' => 'rgt',
										'id' => 'node_id',
										'title' => 'label'));

			// check tree exists
			if ( ! $this->EE->db->table_exists('exp_taxonomy_tree_'.$tree))
			{
				// need to think about this @todo
				return NULL;
			}
			
			if(!isset($data['override_url']))
			{
				$data['override_url'] = '';
			}
			
			$parent_node = $mpttree->get_node_by_nodeid($data['parent_node_id']);			
			
			$parent_node_lft = $parent_node['lft'];
			
			$extra = $this->EE->input->post('extra');	
			if($extra)
			{
				$extra = $this->EE->security->xss_clean($extra);
				$extra = serialize($extra);
			}
			
			// get our array together for the insert/update
			$taxonomy_data = array(
							'node_id'			=> '',
							'label'				=> htmlspecialchars($data['label'], ENT_COMPAT, 'UTF-8'),
							'entry_id'			=> $this->settings['entry_id'],
							'template_path'		=> (isset($data['template']) ? $data['template'] : NULL),
							'custom_url'		=> (isset($data['use_page_uri']) ? '[page_uri]' : $data['override_url']),
							'extra'				=> $extra
							);

			$taxonomy_data = $this->EE->security->xss_clean($taxonomy_data);

			// are we adding a new node?				
			if($data['submission_type'] =='new' && $data['label'] != '')
			{
				// easy, just insert it
				$mpttree->append_node_last($parent_node_lft,$taxonomy_data);
				$this->set_last_update_timestamp($tree);
			}

			// or are we editing
			if($data['submission_type'] =='edit' && $data['label'] != '')
			{

				// fetch the existing node values
				$node = $mpttree->get_node_by_entry_id($this->settings['entry_id']);

				// what is the existing parent value
				$existing_parent = $mpttree->get_parent($node['lft'],$node['rgt']);

				$taxonomy_data['node_id'] = $node['node_id'];

				// update/insert the values

				// check if the submitted parent is different
				if($parent_node_lft != $existing_parent['lft'] && $parent_node_lft != '')
				{
					
					// find the parent node that's intended
					$parent_node_id = $mpttree->get_node($parent_node_lft);
					
					// delete the node and promote the children
					$mpttree->delete_node($node['lft']);
					
					// now the lft values have possibly changed by the above delete
					// find the intended parent node by its node_id, as that hasn't changed
					$new_parent = $mpttree->get_node_byid($parent_node_id['node_id']);
					
					// insert the node!
					$mpttree->append_node_last($new_parent['lft'],$taxonomy_data);

				}
				else
				{
					// we're just updating the label or the template
					$mpttree->update_node($node['lft'],$taxonomy_data);
				}
				
				$this->set_last_update_timestamp($tree);

			}

		}
		
		public function validate($data)
		{
			return TRUE;
		}
		
		public function save_settings($data)
		{
			return array(
				'tree_id'				=> $this->EE->input->post('tree_id'),
				'enable_pages_mode'		=> ($this->EE->input->post('enable_pages_mode')) ? $this->EE->input->post('enable_pages_mode') : NULL,
				'hide_template_select'	=> ($this->EE->input->post('hide_template_select')) ? $this->EE->input->post('hide_template_select') : NULL
			);
		}

		public function display_settings($data)
		{
			
			// fetch the trees available on this site
			$query = $this->EE->db->get_where('exp_taxonomy_trees',array('site_id' => $this->EE->config->item('site_id')));
			
			//build the select options
			$options = array();
			
			// give the options for which tree to associate with this field
			foreach($query->result_array() as $row)
			{
				$options[$row['id']] = $row['label'];
			}
			
			if(!isset($data['tree_id']))
			{
				$data['tree_id'] = '';
			}

 			$this->EE->table->add_row(
 				$this->EE->lang->line('select_tree'),
				form_dropdown('tree_id', $options, $data['tree_id'])
 			);
 			
 			
 			// give the option to run under pages mode
 			$enable_pages_mode = NULL;
 			
 			if(isset($data['enable_pages_mode']))
			{
				$enable_pages_mode = TRUE;
			}
 			
 			$pages_mode_checkbox_options = array(
			    'name'        => 'enable_pages_mode',
			    'id'          => 'enable_pages_mode',
			    'value'       => '1',
			    'checked'     => $enable_pages_mode
		    );
		     			
 			$this->EE->table->add_row(
 				$this->EE->lang->line('enable_pages_mode'),
				form_checkbox($pages_mode_checkbox_options)
 			);
 			
 			
 			// give the option to hide template select
 			// essentially forces publishing via the pages module,
 			// or the taxonomy interface
 			$hide_template_select = NULL;
 			
 			if(isset($data['hide_template_select']))
			{
				$hide_template_select = TRUE;
			}
			 			
 			$hide_template_select_checkbox_options = array(
			    'name'        => 'hide_template_select',
			    'id'          => 'hide_template_select',
			    'value'       => '1',
			    'checked'     => $hide_template_select
		    );
		     			
 			$this->EE->table->add_row(
 				'&nbsp; <img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" /> '.$this->EE->lang->line('hide_template_select'),
				form_checkbox($hide_template_select_checkbox_options)
 			);
 			
 		}			


		function install()
		{
			//nothing
		}

		function unsinstall()
		{
			//nothing
		}
		
		
		// sets the last_updated timestamp for a tree
		private function set_last_update_timestamp($tree_id)
		{
			$id = (isset($tree_id)) ? $tree_id : 0;
			
	    	$time = time();
	    	
			$data = array(
			   'last_updated' => $time
			);
			
			$this->EE->db->where('id', $id);
			$this->EE->db->update('exp_taxonomy_trees', $data);
	
	
		}
		
		
	}
	//END CLASS
	
/* End of file ft.taxonomy.php */