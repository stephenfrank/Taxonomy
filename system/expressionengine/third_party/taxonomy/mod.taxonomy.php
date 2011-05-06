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
 * Taxonomy Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 */

class Taxonomy {
	
	public $return_data;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		
		$this->EE->load->library('Ttree');
		
	}
	
	// ----------------------------------------------------------------
	
	
	/**
	 * exp:taxonomy:nav
	 * Generates a heirarchical list of nodes as an unordered list
	 */
	function nav($str = "")
	{
		
		$tree_id = (int) $this->EE->TMPL->fetch_param('tree_id');

		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id))
			return false;

		$str = $this->EE->TMPL->tagdata;
		$r = '';
		$tree_array = array();
		
		$options = array();
		$options['depth'] 			= (int) $this->_get_param('depth', 100);
		$options['display_root'] 	= $this->_get_param('display_root', 'yes');
		$options['root_lft'] 			= (int) $this->_get_param('root_node_lft', 1);
		$options['root_node_id'] 	= $this->_get_param('root_node_id');
		$options['root_node_entry_id'] 	= (int) $this->_get_param('root_node_entry_id');
		$options['ul_css_id'] 		= $this->_get_param('ul_css_id');
		$options['ul_css_class'] 	= $this->_get_param('ul_css_class');
		$options['hide_dt_group'] 	= $this->_get_param('hide_dt_group');
		$options['url_title']  		= $this->_get_param('url_title');
		$options['auto_expand']  	= $this->_get_param('auto_expand', 'no');
		$options['style']  			= $this->_get_param('style', 'nested');
		$options['path'] 			= NULL;
		$options['entry_status']  	= ( $this->_get_param('entry_status') ) ? explode('|', $this->_get_param('entry_status')) : array('open');
		$options['entry_id'] 		= ( $this->_get_param('entry_id') != '{entry_id}' ) ? $this->_get_param('entry_id') : '';
		$options['active_branch_start_level'] = (int) $this->_get_param('active_branch_start_level', 0);
		$options['node_active_class'] = $this->_get_param('node_active_class', 'active');
		
		
		if($options['url_title'])
		{
			$options['root_entry_id'] = $this->EE->ttree->get_entry_id_from_url_title($options['url_title']);
		}

		if($options['entry_id'])
		{
			$here = $this->EE->ttree->get_node_by_entry_id($options['entry_id'], $tree_id);
			if($here)
			{
				$options['path'] = $this->EE->ttree->get_parents_crumbs($here['lft'],$here['rgt'],$tree_id);
			}
		}
		
		// if we're just starting from the root, we just grab from our session array for performance.
		if(!$options['root_node_entry_id'] && !$options['root_node_id'])
		{
			$tree_array = $this->EE->session->cache['taxonomy']['tree'][$tree_id]['tree_array'];
		}
		// we're grabbing a root node from further down the tree, just query it
		else
        {
        	$tree_array = $this->EE->ttree->tree_to_array($options['root_lft'], $options['root_node_entry_id'], $options['root_node_id']);
        }
		
		// are we starting from x levels down? post process the tree array
		if($options['entry_id'] && $options['active_branch_start_level'] > 0)
        {
            $tree_array = array($this->EE->ttree->_find_in_tree($tree_array, $options['entry_id'], $options['depth'],$options['active_branch_start_level']));
        }
		
		
		$r = ($tree_array != array(0)) ? $this->EE->ttree->build_list($tree_array, $str, $options) : '';
		
		// unset the node_count incase multiple trees are being output
		if (isset($this->EE->session->cache['taxonomy_node_count']))
		{
			$this->EE->session->cache['taxonomy_node_count'] = 1;
		}
		// ditto with prev level counter
		if (isset($this->EE->session->cache['taxonomy_node_previous_level']))
		{
			$this->EE->session->cache['taxonomy_node_previous_level'] = 0;
		}
		
		return $r;
		
	}
	
	
	// ----------------------------------------------------------------
	
	
	/**
	 * exp:taxonomy:breadcrumbs
	 * Generates a breadcrumb trail from the current node with a specified entry_id
	 */
	function breadcrumbs()
	{
	
		$tree_id = $this->_get_param('tree_id');
				
		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id))
			return false;
		
		$return_data = '';
		
		$display_root = $this->EE->TMPL->fetch_param('display_root');
		$wrap_li 	= ($this->EE->TMPL->fetch_param('wrap_li') == 'yes') ? TRUE : FALSE;
		$entry_id = $this->_get_param('entry_id');
		$delimiter = $this->_get_param('delimiter', ' &rarr; ');
		$include_here = $this->_get_param('include_here');
		// remove the delimiter if we're wrapping in <li>
		
		if($wrap_li) $delimiter = NULL;
		
		$hide_dt_group 	= $this->_get_param('hide_dt_group', NULL);

		$here = $this->EE->ttree->get_node_by_entry_id($entry_id);

		if($here != '')
		{
			$path = $this->EE->ttree->get_parents_crumbs($here['lft'],$here['rgt']);
			
			$depth = 0;	
				
			foreach($path as $crumb)
			{

	    		$template_group = ($crumb['is_site_default'] == 'y' && $hide_dt_group == "yes") ? '' : '/'.$crumb['group_name']; 
	    		
	    		// remove index from template names
				$template_name = ($crumb['template_name'] != 'index') ?	'/'.$crumb['template_name'] : ''; 
				$url_title = '/'.$crumb['url_title'];
				
				// build our node_url
				$node_url = $this->EE->functions->fetch_site_index();

				// override template and entry slug with custom url if set
				if($crumb['custom_url'] == "[page_uri]")
				{
	    			$site_id = $this->EE->config->item('site_id');
	    			$node_url .= $this->EE->ttree->entry_id_to_page_uri($crumb['entry_id'], $site_id);
				}
				elseif($node_url[0] == "#")
    			{
    				$node_url = $data['custom_url'];
    			}
				elseif($crumb['custom_url'] != "")
				{
					// if its a relative link, add our site index
					$node_url = ((substr(ltrim($node_url), 0, 7) != 'http://') && (substr(ltrim($node_url), 0, 8) != 'https://') ? $this->EE->functions->fetch_site_index() : '') . $crumb['custom_url'];
				}
				else
				{
					$node_url .= $template_group.$template_name.$url_title;
				}

				// get rid of double slashes, and trailing slash
				$node_url 	= rtrim($this->EE->functions->remove_double_slashes($node_url), '/');
				
				if($display_root =="no" && $depth == 0)
				{
					$return_data .= '';
				}
				else
				{
					$return_data .= ($wrap_li) ? '<li>' : '';
					$return_data .= '<a href="'.$node_url.'">'.$crumb['label'].'</a>'.$delimiter;
					$return_data .= ($wrap_li) ? "</li>\n" : '';
				}
				
				$depth++;
				
			}
			
			if($include_here != 'no')
			{
				$return_data .= ($wrap_li) ? '<li>' : '';
				$return_data .= $here['label'];
				$return_data .= ($wrap_li) ? "</li>\n" : '';
			}
			else
			{
				// pop the last delimiter off
				$return_data = rtrim($return_data, $delimiter);
			}
			
		}	
	
		return $return_data;
	}
	
	
	
	/**
	 * exp:taxonomy:node_url
	 * Returns the url of a node according to taxonomy by passing tree_id and entry_id
	 */
	function node_url()
	{
	
		$tree_id = $this->_get_param('tree_id');
				
		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id))
			return false;
		
		$entry_id = $this->_get_param('entry_id');
		$hide_dt_group = $this->_get_param('hide_dt_group');
		
		$return_data = '';
		$entries = array();
		$url_title = '';
		$template_group = '';
		$template = '';
		
		// stash node urls in the session array if they're not set
		if ( ! isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['entry_urls']))
		{
			
			$nodes_array = $this->EE->ttree->node_urls_array();	
			
			foreach($nodes_array as $node)
			{
				// no custom url, just mesh /group/template/url_title together
				if($node['custom_url'] == "")
				{
					// strip the group name if it's the site default and param has been supplied
					$template_group = ($node['is_site_default'] == 'y' && $hide_dt_group == "yes") ? '' : '/'.$node['group_name'];
					// strip out index
					$node['template_name'] = ($node['template_name'] == 'index') ? '' : $node['template_name'];
					$entries[ $node['entry_id'] ] = $this->EE->functions->fetch_site_index().$template_group.'/'.$node['template_name'].'/'.$node['url_title'];
				}
				else
				{
					// are we using a page uri
					if($node['custom_url'] == '[page_uri]')
					{
						$entries[ $node['entry_id'] ] =  $this->EE->functions->fetch_site_index().$this->EE->ttree->entry_id_to_page_uri($node['entry_id']);
					}
					// relative anchor perhaps...
					elseif($node['custom_url'][0] == "#")
	    			{
	    				$entries[ $node['entry_id'] ] = $node['custom_url'];
	    			}
	    			else
	    			{
	    				// external link or relative?
	    				// (substr(ltrim($node_url), 0, 7) != 'http://') && (substr(ltrim($node_url), 0, 8) != 'https://')
	    				$entries[ $node['entry_id'] ] = ((substr(ltrim($node['custom_url']), 0, 7) != 'http://') && (substr(ltrim($node['custom_url']), 0, 8) != 'https://') ? 
	    												$this->EE->functions->fetch_site_index() : '') . $node['custom_url'];
	    			}
				
				}
				
				$entries[ $node['entry_id'] ] = $this->EE->functions->remove_double_slashes( $entries[ $node['entry_id'] ] );

			}
			
			$this->EE->session->cache['taxonomy']['tree'][$tree_id]['entry_urls'] = $entries;
			
		}
		
		return ( isset($this->EE->session->cache['taxonomy']['tree'][$tree_id]['entry_urls'][$entry_id]) ? 
						$this->EE->session->cache['taxonomy']['tree'][$tree_id]['entry_urls'][$entry_id] : '');
		
	
	}
	
	// ----------------------------------------------------------------
	
	
	function get_children_ids()
	{

		$tree_id = $this->_get_param('tree_id');
		$entry_id = $this->_get_param('entry_id');

		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id) OR $entry_id == '')
			return false;
				
		$here = $this->EE->ttree->get_node_by_entry_id($entry_id);
		$immediate_children = array();
		$child_entry_ids = '';

		if($here != '')
		{
			$immediate_children = $this->EE->ttree->get_children_ids($here['node_id']);

			foreach($immediate_children as $child)
			{
				$child_entry_ids .= ($child['entry_id'] != 0) ? $child['entry_id'].'|' : '';
			}
		}

		return rtrim($child_entry_ids, '|');

	}
	
	
	
	
	function get_sibling_ids()
	{
	
		$tree_id = $this->_get_param('tree_id');
		$entry_id = $this->_get_param('entry_id');
		$include_current = $this->_get_param('include_current');
		$return = '';

		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id) OR $entry_id == '')
			return false;
		
		// where are we
		$here = $this->EE->ttree->get_node_by_entry_id($entry_id);
				
		// have we found the node, and it's not the root node
		if($here == "" OR $here['lft'] == '1')
		{
			return false;
		}
				
		$parent = $this->EE->ttree->get_parent($here['lft'],$here['rgt']);
		$siblings = $this->EE->ttree->get_children_ids($parent['node_id']);

		foreach($siblings as $sibling)
		{
			$return .= $sibling['entry_id'].'|';
		}
		
		// do we want the entry_id of the current node?
		if($include_current != 'yes')
		{
			$return = str_replace($here['entry_id'].'|', '', $return);
		}

		return rtrim($return, "|");
	
	}
	

	
	public function simple_nav()
	{
		$tree_id = $this->EE->TMPL->fetch_param('tree_id');

		if ( ! $this->EE->ttree->check_tree_table_exists($tree_id))
			return false;
			
		return $this->build_simple_list();
		
	}
	
	
	function build_simple_list($session_data = NULL)
	{
		$session_data = (isset($session_data)) ? $session_data : $this->EE->session->cache['taxonomy']['tree'][1]['tree_array'];
		$str = "<ul>\n";
	    foreach($session_data as $data)
	    {
	    	
	        $str .= "<li>";
	        $str .= '<a href="">'.$data['label'].'</a>'; // whatever you want between the <li> </li>
	        if(isset($data['children'])){
	            $str .= $this->build_simple_list($data['children']);
	        }
	        $str .= "</li>\n";
	    }
	    $str .= "</ul>\n";
	    return $str;
	}
	
	
	/**
     * Helper function for getting a parameter
	 */		 
	function _get_param($key, $default_value = '')
	{
		$val = $this->EE->TMPL->fetch_param($key);
		
		if($val == '') {
			return $default_value;
		}
		return $val;
	}
	
	
}
/* End of file mod.taxonomy.php */
/* Location: /system/expressionengine/third_party/taxonomy/mod.taxonomy.php */