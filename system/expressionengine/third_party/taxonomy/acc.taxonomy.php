<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Taxonomy Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Accessory
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */
 

class Taxonomy_acc {

	var $name			= 'Taxonomy Accessory';
	var $id				= 'taxonomy_acc';
	var $version		= '1.0';
	var $description	= 'Adds a Taxonomy dropdown to the CP main menu';
	var $sections		= array();

	
	function __construct()
	{
		$this->EE =& get_instance();
		$this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=taxonomy';
		$this->edit_tree_base = $this->base.AMP.'method=edit_nodes'.AMP.'tree_id=';
	}
	

	function set_sections()
	{

		$r = '';
		$this->sections[] = '<script type="text/javascript" charset="utf-8">$("#accessoryTabs a.taxonomy_acc").parent().remove();</script>';

		$installed_modules 	= $this->EE->cp->get_installed_modules();
		
		if (array_key_exists('taxonomy', $installed_modules)) {
		
			$query = $this->EE->db->get_where('exp_taxonomy_trees',array('site_id' => $this->EE->config->item('site_id')));
			
			if ($query->num_rows() > 0)
			{
				foreach($query->result_array() as $row)
				{
					$r .= '<li><a href=\''.$this->edit_tree_base.$row['id'].'\'>'.$row['label'].'</a></li>';
				}
			}
			
			$this->EE->cp->add_to_head('
			<script type="text/javascript">
			
				$(document).ready(function(){
				
				var trees = "'.$r.'";

				var taxonomy_menu = "<li class=\'parent\'><a class=\'first_level\' href=\'#\'>Taxonomy</a><ul>" + trees + "<li class=\'nav_divider\'></li><li><a href=\''.$this->base.'\'>Overview</a></li><li class=\'bubble_footer\'></li></ul></li>";
					
					// if you want the menu to appear elsewhere, edit the selector here
					$("ul#navigationTabs > li.parent:nth-child(3)").before(taxonomy_menu);
				});
			
			</script>
			
			');
		}

	}
	
	function update()
	{
		return TRUE;
	}
	
}
/* End of file acc.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/acc.taxonomy.php */