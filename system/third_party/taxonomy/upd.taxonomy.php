<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Build nested sets from channel entries
 *
 * @package		ExpressionEngine
 * @subpackage	Taxonomy
 * @category	Modules
 * @author    	Iain Urquhart <shout@iain.co.nz> (@iain)
 * @copyright 	Copyright (c) 2010 Iain Urquhart
 * @license   	http://creativecommons.org/licenses/MIT/  MIT License
 */
class Taxonomy_upd {

	var $version        = '1.03';
	var $module_name 	= "Taxonomy";
	
	function Taxonomy_upd( $switch = TRUE ) 
	{ 
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/**
	* Installer for the Taxonomy module
	*/
    function install() 
	{

		$this->EE->load->dbforge();

		// register the module
		$data = array(
			'module_name' 	 => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'y'
		);

		$this->EE->db->insert('modules', $data);


		// build the taxonomy_trees table
		// this contains keys for each taxonomy_tree_x table generated by the user
		$fields = array(
						'id' 		=> array('type' => 'int',
											 'constraint' => '10',
											 'unsigned' => TRUE,
											 'auto_increment' => TRUE),
											 
						'site_id' 	=> array('type'	=> 'int', 
											 'constraint'	=> '10'),
											 
						'label'		=> array('type' => 'varchar',
											 'constraint' => '250'),
											 
						'template_preferences'	=> array('type' => 'varchar', 
														 'constraint' => '250', 
														 'default' => 'all'),
														 
						'channel_preferences'	=> array('type' => 'varchar', 
														 'constraint' => '250', 
														 'default' => 'all'),
														 
						'last_updated'			=> array('type' => 'int', 
														 'constraint' => '10'),
														 
						'pages_mode' 			=> array('type'	=> 'int', 
											 			 'constraint' => '1')
						);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('taxonomy_trees');
		unset($fields);
		
		
		// build the module config table
		// holds the tinyest of settings for now,
		// I imagine there will be more to addin due course
		$fields = array(
			            'id' => array(	'type' => 'int',
			            				'constraint' => '10', 
			            				'unsigned' => TRUE, 
			            				'auto_increment' => TRUE,),
			            				
			            'site_id' 	 => array(	'type' => 'int', 
			            						'constraint' => '10', 
			            						'unsigned' => TRUE,),
			            						
			            'asset_path' => array(	'type' => 'varchar', 
			            						'constraint' => '250')
			        );

        $this->EE->dbforge->add_field($fields);
        $this->EE->dbforge->add_key('id', TRUE);
        $this->EE->dbforge->create_table('taxonomy_config');
		unset($fields);
		
		// insert our default config settings for this site
		$settings_data = array(
			               'id' => NULL,
			               'site_id' => $this->EE->config->item('site_id'),
			               'asset_path' => 'expressionengine/third_party/taxonomy/views/taxonomy_assets/'
			            	);

		$this->EE->db->insert('taxonomy_config', $settings_data); 
													
		return TRUE;

	}

	
	/**
	 * Uninstall the Taxonomy module
	 */
	function uninstall()
	{
	
		$this->EE->load->dbforge();
		
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->module_name));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', $this->module_name);
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', $this->module_name);
		$this->EE->db->delete('actions');

		$this->EE->db->where('class', $this->module_name.'_mcp');
		$this->EE->db->delete('actions');
		
		$query = $this->EE->db->get('exp_taxonomy_trees');	
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$this->EE->dbforge->drop_table('taxonomy_tree_'.$row['id']);
			}
		}
		
		$this->EE->dbforge->drop_table('taxonomy_trees');
		$this->EE->dbforge->drop_table('taxonomy_config');
										
		return TRUE;
	}
	
	/**
	 * Update the Taxonomy module
	 * 
	 * @param $current current version number
	 * @return boolean indicating whether or not the module was updated 
	 */

	function update($current = '')
	{
		// check we're up to date
		if ($current == $this->version)
		{
			return FALSE;
		}
		
		// msm compatability
		if ($current < 0.23) 
		{
			$this->EE->load->dbforge();
			$fields = array(
                        	'site_id' => array(	'type' => 'int', 
                        						'constraint' => '4', 
                        						'default' => $this->EE->config->item('site_id'))
							);

			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
		}
		
		// addition of the preferences table
		if ($current < 0.51) 
		{
			$this->EE->load->dbforge();
			$fields = array(
			            'id'		 => array(	'type' => 'int', 
			            						'constraint' => '10', 
			            						'unsigned' => TRUE, 
			            						'auto_increment' => TRUE,),
			            						
		            	'site_id' 	 => array(	'type' => 'int', 
		            							'constraint' => '10', 
		            							'unsigned' => TRUE, 
		            							'default' => $this->EE->config->item('site_id')),
		            							
		            	'asset_path' => array(	'type' => 'varchar', 
		            							'constraint' => '250', 
		            							'default' => 'expressionengine/third_party/taxonomy/views/taxonomy_assets/')
		        							);

	        $this->EE->dbforge->add_field($fields);
	        $this->EE->dbforge->add_key('id', TRUE);
	        $this->EE->dbforge->create_table('taxonomy_config');
			
			unset($fields);
			
			$settings_data 	= array(
			               			'id' => NULL,
			               			'site_id' => $this->EE->config->item('site_id'),
			               			'asset_path' => 'expressionengine/third_party/taxonomy/views/taxonomy_assets/'
			            			);
		
			$this->EE->db->insert('taxonomy_config', $settings_data); 
			
		}
		
		
		// added 'last_updated' column to prevent tree corruption from multiple 
		// users editing same tree at the same time
		if($current < 1) 
		{
			$this->EE->load->dbforge();
			$fields = array('last_updated' => array('type' => 'int', 'constraint' => '10'));
			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
		}

		
		return TRUE;
		
	}
    
}

/* End of file upd.taxonomy.php */ 
/* Location: ./system/expressionengine/third_party/taxonomy/upd.taxonomy.php */ 