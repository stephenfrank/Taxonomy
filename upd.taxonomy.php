<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  Taxonomy Module for ExpressionEngine 2
 *
 * @package		ExpressionEngine
 * @subpackage	Taxonomy
 * @category	Modules
 * @author    	Iain Urquhart <shout@iain.co.nz>
 * @copyright 	Copyright (c) 2010 Iain Urquhart
 * @license   	http://creativecommons.org/licenses/MIT/  MIT License
*/

class Taxonomy_upd {

	var $version = '0.41';
	
	function Taxonomy_upd()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
	

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		$this->EE->load->dbforge();
		
		$data = array(
			'module_name' => 'Taxonomy' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'y'
		);

		$this->EE->db->insert('modules', $data);

		// build the taxonomy_trees table
		// this contains keys for each taxonomy_tree_x table generated by the user
		$fields = array(
						'id'		  		=> array(	'type' 			 => 'int',
														'constraint'	 => '10',
														'unsigned'		 => TRUE,
														'auto_increment' => TRUE),
						'site_id'			=> array('type'	=> 'int', 'constraint'	=> '10'),
						'label'		  		=> array('type' => 'varchar', 'constraint' => '250'),
						'template_preferences'		=> array('type' => 'varchar', 'constraint' => '250', 'default' => 'all'),
						'channel_preferences'		=> array('type' => 'varchar', 'constraint' => '250', 'default' => 'all')
						);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table('taxonomy_trees');	
		
		unset($fields);
		
		return TRUE;

	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => 'Taxonomy'));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', 'Taxonomy');
		$this->EE->db->delete('modules');
		
		// grab the trees...
		$query = $this->EE->db->get('exp_taxonomy_trees');	
		
		// then drop 'em
		foreach($query->result_array() as $row)
		{
			$this->EE->dbforge->drop_table('taxonomy_tree_'.$row['id']);
		}

		$this->EE->dbforge->drop_table('taxonomy_trees');


		return TRUE;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	
	function update($current='')
	{
		
		if ($current == $this->version)
		{
			return FALSE;
		}
			
		if ($current < 0.23) 
		{
			$this->EE->load->dbforge();
			$fields = array(
                        'site_id' => array('type'	=> 'int', 'constraint'	=> '4', 'default' => $this->EE->config->item('site_id'))
							);
			$this->EE->dbforge->add_column('taxonomy_trees', $fields);
			
			// $.ee_notice("Module updated to v0.23");
			
		} 
	}
	
}
/* END Class */

/* End of file upd.taxonomy.php */
/* Location: ./system/expressionengine/third_party/modules/nodetree/upd.taxonomy.php */