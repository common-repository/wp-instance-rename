<?php   
/* 
Plugin Name: Wordpress renaming tool by Vlajo
Plugin URI:  
Description: Plugin for renaming wordpress instance 
Author: Vlajo
Version: 1.0 
Author URI: 
License: GPLv2 or later
*/ 

/*	Copyright 2014  Vlajo  (email : vlajo2012@gmail.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
session_start();
$GLOBAL['authorize']=false;
function renaming_admin() {	
	$GLOBAL['authorize']=true;
	include('variables.php');  
    include('renaming_import_admin.php');  
	
}  
  
function renaming_admin_actions() {

	if ( is_admin() ){ 		
		// admin actions
		add_options_page("Wordpress Rename", "Wordpress Rename", 2, "Wordpress_Rename", "renaming_admin");		
	} else {		
		// non-admin enqueues, actions, and filters		
		
	}      
}

add_action('admin_menu', 'renaming_admin_actions'); 
?>