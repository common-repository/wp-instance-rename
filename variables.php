<?php
if ( !$GLOBAL['authorize'] ) die ("Direct access not permitted");
class SetVariables{
		public $myMagicData = array();//holds all data
		public $mysql_location_start;
		public $database_backup_dump;
		public $wordpress_root;		
		public $wordpress_document_root;
		public $plugin_location_start;
		public $backup_folder;
		public $renamed;
		public $message;
		public $message_type;
		public $dbname;
		public $dbhost;
		public $dbuser;
		public $dbpass;
		public $dump_db_to_file;
		public $drop_previous_database;
		public $old_folder_location;
		public $new_folder_location;
		public $old_url_location;
		public $new_url_location;
		public $new__db_name;	
		public $backup_location_path;	
		
		public $plugin_name;
		public $platform;
		public $wpdbb_content_dir;
		public $wpdbb_content_url;
		public $wpdbb_plugin_dir;		
		public $referer_check_key;
		
		function getVariable($name){				
			return array_key_exists($name, $this->myMagicData) ? $this->myMagicData[$name] : null;			
		}
		
		function setVariable($name,$value){			
			$this->myMagicData[$name] = $value;			
		}	
		
		public function __construct(){	
			$platform_name="";
			if(stripos(PHP_OS,"WIN") !== false){			
				$platform_name ="Windows";
				
			}
			else if(stripos(PHP_OS,"LIN") !== false){
				$platform_name ="Linux";			
			}
			$this->setVariable("platform",$platform_name);
			$this->setVariable("plugin_name","wordpress_rename");
			//$this->setVariable("referer_check_key",$this->getVariable("plugin_name").'-rename_'.DB_NAME.'-date_'.date("Y-m-d H:i:s"));
			$this->setVariable("referer_check_key",$this->getVariable("plugin_name").'-rename_'.DB_NAME.'-date_'.date("Y-m-d"));
			
			$wpdbb_content_dir = ( defined('WP_CONTENT_DIR') ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
			$wpdbb_content_url = ( defined('WP_CONTENT_URL') ) ? WP_CONTENT_URL : get_option('siteurl') . '/wp-content';
			$wpdbb_plugin_dir = ( defined('WP_PLUGIN_DIR') ) ? WP_PLUGIN_DIR : $wpdbb_content_dir . '/plugins';							
			$ini_array;  
			try{
				$ini_array= parse_ini_file("config.ini");			 
			}catch (Exception $e){}
			
			$mysql_start="";
			if($ini_array){
				$mysql_start=$ini_array['mysql_location_start'];
				$database_backup_dump=$ini_array['database_backup_dump'];
			}else if($_SERVER["MYSQL_HOME"]){
				$mysql_start=$_SERVER["MYSQL_HOME"]."\\";
			}
			if(file_exists($mysql_start)){
				$this->setVariable("mysql_location_start",str_replace('\\', '/', $mysql_start));
			}
			
			$this->setVariable("database_backup_dump",$database_backup_dump);
			$this->setVariable("wordpress_root",str_replace('\\', '/', ABSPATH));
			$this->setVariable("wordpress_document_root",$_SERVER["DOCUMENT_ROOT"]);
			$this->setVariable("wpdbb_content_dir",str_replace('\\', '/', $wpdbb_content_dir));
			$this->setVariable("wpdbb_content_url",str_replace('\\', '/', $wpdbb_content_url));
			$this->setVariable("wpdbb_plugin_dir",str_replace('\\', '/', $wpdbb_plugin_dir));			 
			$this->setVariable("plugin_location_start",str_replace('\\', '/', $this->getVariable("wpdbb_plugin_dir")."\\".$this->getVariable("plugin_name")."\\"));	
			$this->setVariable("backup_folder",str_replace('\\', '/', $this->getVariable("wpdbb_plugin_dir")."\\".$this->getVariable("plugin_name")."\\backup\\"));	
			$this->setVariable("renamed","_renamed");			 
		}		
	}
$GLOBALS['class'] = new SetVariables();	
?>