<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Taxonomy Module Install/Update File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

class Taxonomy_upd {
	
	public $version = '2.0';
	
	private $EE;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		$mod_data = array(
			'module_name'			=> 'Taxonomy',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> "y",
			'has_publish_fields'	=> 'n'
		);
		
		$this->EE->db->insert('modules', $mod_data);
		
		// build the taxonomy_trees table
		// each tree gets a row here with various settings
		
		$this->EE->load->dbforge();
		
		$fields = array(
						'id' => array('type' => 'int',
									  'constraint' => '10',
									  'unsigned' => TRUE,
									  'auto_increment' => TRUE),

						'site_id' => array('type'	=> 'int', 
										   'constraint'	=> '10'),

						'label'	=> array('type' => 'varchar',
										 'constraint' => '250'),

						'template_preferences' => array('type' => 'varchar', 
														'constraint' => '250', 
														'default' => 'all'),

						'channel_preferences' => array('type' => 'varchar', 
													   'constraint' => '250', 
													   'default' => 'all'),

						'last_updated' => array('type' => 'int', 
												'constraint' => '10'),

			 			'fields' => array('type' => 'text'),

			 			'permissions' => array('type' => 'varchar',
											   'constraint' => '250'),
						
						'tree_array' => array('type' => 'longtext')
						);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('taxonomy_trees');
		unset($fields);

		return TRUE;
	}

	// ----------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
		
		$this->EE->load->dbforge();
		
		$mod_id = $this->EE->db->select('module_id')
								->get_where('modules', array(
									'module_name'	=> 'Taxonomy'
								))->row('module_id');
		
		$this->EE->db->where('module_id', $mod_id)
					 ->delete('module_member_groups');
		
		$this->EE->db->where('module_name', 'Taxonomy')
					 ->delete('modules');
		
		// do we have any trees
		$query = $this->EE->db->get('exp_taxonomy_trees');	
		
		// drop each tree table if we do
		if ($query->num_rows() > 0)
		{
			
			
			foreach($query->result_array() as $row)
			{
				$this->EE->dbforge->drop_table('taxonomy_tree_'.$row['id']);
			}
		}
		
		// laters
		$this->EE->dbforge->drop_table('taxonomy_trees');
		
		return TRUE;
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		// If you have updates, drop 'em in here.
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
		
		if($current < 1.100) 
		{
			// add the extra col
			$this->EE->load->dbforge();
			$fields = array('extra' => array('type' => 'text'));
			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
			
			// stoopid me set it to varchar(255) initially, not enough room for fields I think...
			$query = $this->EE->db->get('exp_taxonomy_trees');	
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$fields = array('extra' => array('name' => 'extra', 'type' => 'TEXT'));
					$this->EE->dbforge->modify_column('taxonomy_tree_'.$row['id'], $fields);
				}
			}
	
		}
		
		
		if ($current < '1.2.3') 
		{
			$this->EE->load->dbforge();
			$fields = array('permissions' => array('type' => 'varchar','constraint' => '250'));
			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
		}
		
		// --------------------------------------------------------------------
		// 2.0 upgrades...
		if ($current < '2.0') 
		{
			$this->EE->load->dbforge();
			
			// for some reasone I can't load the lib using regular method?
			include_once PATH_THIRD.'taxonomy/libraries/Ttree.php';
			
			$ttree = new Ttree;

			// drop pages mode column, never used this in the end
			if ($this->EE->db->field_exists('pages_mode', 'taxonomy_trees'))
			{
				$this->EE->dbforge->drop_column('taxonomy_trees', 'pages_mode');
			}
			
			// --------------------------------------------------------------------
			
			
			// 'extra' column is renamed to 'fields' in exp_taxonomy_trees
			if ($this->EE->db->field_exists('extra', 'taxonomy_trees'))
			{
				$fields = array('extra' => array(
											'name' => 'fields',
											'type' => 'TEXT',
											));

				$this->EE->dbforge->modify_column('taxonomy_trees', $fields);
				unset($fields);
			}
			
			
			// --------------------------------------------------------------------
			
			
			// build our tree arrays and rename the 'extra' column for each tree table

			$fields = array('tree_array' => array('type' => 'longtext'));
			
			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
			
			unset($fields);
			
			$trees = $this->EE->db->get('taxonomy_trees')->result_array();
			
			if(count($trees))
			{

				// loop through our trees
				foreach ($trees as $tree)
				{
					
					
					// rename the 'extra' column
					$fields = array('extra' => array(
											'name' => 'field_data',
											'type' => 'TEXT',
											));

					$this->EE->dbforge->modify_column('taxonomy_tree_'.$tree['id'], $fields);
					unset($fields);
					
					// now we need to go through each node for this tree and base64encode all the field data
					
					$nodes = $this->EE->db->get_where('taxonomy_tree_'.$tree['id'], array('field_data !=' => ''))->result_array();
					
					if(count($nodes))
					{
						foreach($nodes as $node)
						{
							$data['field_data'] = base64_encode( $node['field_data'] );
							$this->EE->db->where('node_id', $node['id']);
							$this->EE->db->update('taxonomy_tree_'.$tree['id'], $data);
							unset($data);
						}
					}
					
					// rebuild the arrays
					$ttree->set_table($tree['id']);
					$ttree->rebuild_tree_array($tree['id']);

				}
			}
			
			
			// --------------------------------------------------------------------
			
			
			// remove the tab association, not needed.
			
			$data['has_publish_fields'] = 'n';
			$this->EE->db->where('module_name', 'Taxonomy');
			$this->EE->db->update('modules', $data);
			
			// END 2.0 Upgrades
			// --------------------------------------------------------------------
		}
		
		return TRUE;
		
		
	}
	
}
/* End of file upd.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/upd.taxonomy.php */