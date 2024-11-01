<?php

	if ( !$GLOBAL['authorize'] ) die ("Direct access not permitted");
	
	function fileName($backup_folder,$dbname,$renamed){
		/***************************************************************************************************************************************************/ 
		$file_name="";
		$file_name = $backup_folder.$dbname . "_" . date("Y-m-d").$renamed.".sql";
		return $file_name;
		/***************************************************************************************************************************************************/
	}
	
	function backupDatabase($dump_db_to_file,$dbhost,$dbuser,$dbpass,$dbname,$backup_folder,$mysql_location_start,$database_backup_dump='',$backup_location_path=''){
		/***************************************************************************************************************************************************/ 
		//backup database just in case		
		if($dump_db_to_file == "Y"){
			
			//user has a dump file
			if(!empty($backup_location_path) && file_exists($backup_location_path)){
				return $backup_location_path;
			}
			if(!empty($database_backup_dump) && file_exists($database_backup_dump)){
				return $database_backup_dump;
			}
		
			// if mysqldump is on the system path you do not need to specify the full path
			// simply use "mysqldump --add-drop-table ..." in this case			
			$dumpfname = fileName($backup_folder,$dbname,"");			
			$command = $mysql_location_start."mysqldump --add-drop-table --allow-keywords --opt -h$dbhost -u$dbuser ";
			
			if ($dbpass) 
					$command.= "-p". $dbpass ." "; 
			$command.= $dbname;
			$command.= " > " . $dumpfname;			
			$command = mysql_real_escape_string($command);		
			$output = exec($command);	
			if((!empty($output)) || ($output === null)){
				throw new Exception('Backup failed');
			}
					
		}
		if(!file_exists($dumpfname)){
			$dumpfname=null;
			throw new Exception('Backup failed');
		}
		return $dumpfname;
		/***************************************************************************************************************************************************/
	}
	
	function renameInFile($dumpfname,$old_wordpress_name,$new_wordpress_name,$dump_db_to_file,$dbhost,$dbuser,$dbpass,$dbname,$backup_folder,$mysql_location_start,$renamed){
	/***************************************************************************************************************************************************/ 
		//new wordpress name different from the old name				
						
		//rename in tables do before dump and import
		//rename wordpress folder name and not db name

		//In the Wordpress address (URL) field, enter the web address of your new blog location.
		//In the Blog address (URL) field, enter the same new location.
		//Now rename your Wordpress installation folder/directory
		//Update links with the new path. (Easiest to do this in the db, and rename all the links directly)			
		
		$dumpfname_new = fileName($backup_folder,$dbname,$renamed);		
		if(file_exists($dumpfname)){			
			if (!copy($dumpfname, $dumpfname_new)) {
				throw new Exception('Rename copy failed'.$dumpfname);
			}
			//changing values inside text files
			$contents=file_get_contents($dumpfname_new);
			$new_contents=str_replace($old_wordpress_name,$new_wordpress_name,$contents);
			file_put_contents($dumpfname_new,$new_contents);			
		}else{
			throw new Exception('Rename failed');
		}
		return $dumpfname_new;
	/***************************************************************************************************************************************************/		
	}
	
	function removeUnnecessaryFiles($dump_db_to_file,$dumpfname,$dumpfname_new){
		if($dump_db_to_file != "Y"){
			if(file_exists($dumpfname)) unlink($dumpfname);
		}
		if(file_exists($dumpfname_new)) unlink($dumpfname_new);		
	}
	
	function renameFolder($wordpress_root,$old_wordpress_name,$new_wordpress_name){
		try{
			if(!empty($new_wordpress_name)){
				if(strcasecmp($new_wordpress_name,$old_wordpress_name) != 0){				
					//rename directory name on file system			
					$old_location=dirname($wordpress_root).'/'.$old_wordpress_name.'/';
					$new_location=dirname($wordpress_root).'/'.$new_wordpress_name.'/';
					if(file_exists($new_location)){
						throw new Exception('New Wordpress location already exists!');
					}
					$status=true;
					if(is_writable($old_location)){
						$status=rename($old_location,$new_location);
						if(!$status){
							throw new Exception('Rename folder failed!');
						}
					}else{
						throw new Exception('Rename folder failed!');
					}				
				}
			}
		}catch(Exception $e){
			throw new Exception('Rename folder failed!');
		}
	}
	
	function copyFolder($wordpress_root,$old_wordpress_name,$new_wordpress_name){
		try{
			set_time_limit(0);
			if(!empty($new_wordpress_name)){
				if(strcasecmp($new_wordpress_name,$old_wordpress_name) != 0){				
					//rename directory name on file system		
					$old_location=dirname($wordpress_root).'/'.$old_wordpress_name;
					$new_location=dirname($wordpress_root).'/'.$new_wordpress_name;
					if(is_writable($old_location)){
						recurse_copy($old_location,$new_location);						
					}else{
						throw new Exception('Copy files failed!');
					}
								
				}
			}
		}catch(Exception $e){
			throw new Exception('Copy files failed!');
		}
	}
	
	function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
			$status=true;
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                $status=copy($src . '/' . $file,$dst . '/' . $file); 
				if(!$status){
					throw new Exception('Copy files failed!');
				}
            } 
        } 
    } 
    closedir($dir); 
}
	
	function removeUnnecessaryFolder($wordpress_root,$old_wordpress_name,$drop_previous_wordpress){
		if($drop_previous_wordpress != "Y"){
			if(file_exists($wordpress_root.$old_wordpress_name)) unlink($wordpress_root.$old_wordpress_name);
		}	
	}
	
	function createNewDatabase($new__db_name,$dbname,$mysql_location_start,$dbuser,$dbpass){
		/***************************************************************************************************************************************************/ 
		//create new db if necessary
		if(!empty($new__db_name)){
			if(strcasecmp($new__db_name,$dbname) != 0){					
				$new_db= @mysql_select_db($new__db_name);
				if(!($new_db)){
					$create_new_db=$mysql_location_start."mysqladmin -u$dbuser ";
					if ($dbpass) 
							$create_new_db.= "-p". $dbpass ." "; 
					$create_new_db.= "create ".$new__db_name;
					$create_new_db = mysql_real_escape_string($create_new_db);	
					$output = exec($create_new_db);					
					if((!empty($output)) || ($output === null)){
						throw new Exception('Database not created!');
					}
					$db= @mysql_select_db($new__db_name);
					if($db){					
						//error_log("Database created ".$new__db_name,0);
					}else{
						throw new Exception('Database not created!');
					}
					
				}else{
					//$GLOBALS['message'].= "Database already exists! ";					
				}
			}
		}
		/***************************************************************************************************************************************************/
	}
	
	function importDatabaseFromFile($filename,$new__db_name,$dbhost,$dbuser,$dbpass,$mysql_location_start){
	/***************************************************************************************************************************************************/ 
		$new_db= @mysql_select_db($new__db_name);
		if($new_db){
			if(file_exists($filename)){
				if(!empty($dbhost) && !empty($dbuser)){
					//if dump file exist, use to import into new database
					$dump_and_import=$mysql_location_start."mysql -h$dbhost -u$dbuser ";
					if ($dbpass) 
							$dump_and_import.= "-p". $dbpass ." "; 																
					$dump_and_import .= $new__db_name;
					$dump_and_import.= " < " . $filename;					
					$dump_and_import = mysql_real_escape_string($dump_and_import);
					$output = exec($dump_and_import);
					if((!empty($output)) || ($output === null)){
						throw new Exception('File not imported in database!');
					}
				}
			}else{
				throw new Exception('File not imported in database!');
			}	
		}else{
			throw new Exception('File not imported in database!');
		}
	/***************************************************************************************************************************************************/
	}
	
	function dropDatabase($drop_previous_database,$database){
		/***************************************************************************************************************************************************/
		if($drop_previous_database == "Y"){
			$db= @mysql_select_db($database);
			if($db){
				$drop_old_db="drop database $database";//drop old db
				$drop_old_db = mysql_real_escape_string($drop_old_db);	
				$result = mysql_query($drop_old_db);			
			}
		}
		/***************************************************************************************************************************************************/
				
	}

	function replaceInString($name,$value,$contents){
		/***************************************************************************************************************************************************/
		$special_str="','";
		$special_str_reg="\',.*\'";
		$special_str_reg_word="(\w+)";
		$pattern ="/".$name.$special_str_reg.$special_str_reg_word."/";
		$replacement=$name.$special_str.$value;
		$contents= preg_replace($pattern, $replacement, $contents);
		return $contents;
		/***************************************************************************************************************************************************/
	}
		
	function change_wordpress_url($dbhost,$new_wordpress_name){
		update_option('siteurl','http://'.$dbhost.'/'.$new_wordpress_name);
		update_option('home','http://'.$dbhost.'/'.$new_wordpress_name);
	}	
			
	function change_wp_config($wordpress_root,$dbhost,$dbuser,$dbpass,$dbname){
		/***************************************************************************************************************************************************/
		
		$wp_config_file=$wordpress_root."/wp-config.php";		
		if(file_exists($wp_config_file)){			
			
			//changing values inside text files
			$contents=file_get_contents($wp_config_file);		
			
			$contents= replaceInString('DB_NAME',$dbname,$contents);
			$contents= replaceInString('DB_USER',$dbuser,$contents);
			$contents= replaceInString('DB_PASSWORD',$dbpass,$contents);
			$contents= replaceInString('DB_HOST',$dbhost,$contents);
			
			$result=file_put_contents($wp_config_file,$contents);	
			if(is_bool($result) && !$result){
				throw new Exception('wp_config.php file not found!');
			}		
		}else{
			throw new Exception('wp_config.php file not found!');
		}
		/***************************************************************************************************************************************************/
	}
			
	function write_variable($variable){
		ob_start();
		var_dump($variable);
		$contents = ob_get_contents();
		ob_end_clean();
		error_log($contents);
		//error_log("variable: ".$variable,0);		
	}	
		
	function init(){		
		setClassVariable("dbhost",isset($_POST['renaming_dbhost'])? htmlentities($_POST['renaming_dbhost']):'');
		setClassVariable("dbname",isset($_POST['renaming_dbname'])? htmlentities($_POST['renaming_dbname']):DB_NAME);
		setClassVariable("dbuser",isset($_POST['renaming_dbuser'])? htmlentities($_POST['renaming_dbuser']):'');
		setClassVariable("dbpass",isset($_POST['renaming_dbpass'])? htmlentities($_POST['renaming_dbpass']):'');
		setClassVariable("dump_db_to_file",isset($_POST['dump_db_to_file'])? htmlentities($_POST['dump_db_to_file']):'');
		setClassVariable("drop_previous_database",isset($_POST['drop_previous_database'])? htmlentities($_POST['drop_previous_database']):'');
		setClassVariable("drop_previous_wordpress",isset($_POST['drop_previous_wordpress'])? htmlentities($_POST['drop_previous_wordpress']):'');
		setClassVariable("old_folder_location",isset($_POST['renaming_old_folder_location'])? htmlentities($_POST['renaming_old_folder_location']):'');
		setClassVariable("new_folder_location",isset($_POST['renaming_new_folder_location'])? htmlentities($_POST['renaming_new_folder_location']):'');
		setClassVariable("old_url_location",isset($_POST['renaming_old_url_location'])? htmlentities($_POST['renaming_old_url_location']):'');
		setClassVariable("new_url_location",isset($_POST['renaming_new_url_location'])? htmlentities($_POST['renaming_new_url_location']):'');
		setClassVariable("new__db_name",isset($_POST['renaming_new_db__name'])? htmlentities($_POST['renaming_new_db__name']):'');		
		setClassVariable("backup_location_path",isset($_POST['backup_location_path'])? htmlentities($_POST['backup_location_path']):'');		
	}	
		
	function getWordpressLocationFolder(){	
		
		$name=getClassVariable("wpdbb_content_dir");
		$name=str_replace('\\', '/', $name);
		$name=dirname($name);	
		$name=substr($name,strrpos($name,'/')+1);
		return $name;
	}
	
	function getWordpressLocationUrl(){
		$url=parse_url(urldecode(site_url()));		
		return str_replace("/","",$url['path']);
	}
	
	function getUrl() {
	  $url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	  $url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	  $url .= $_SERVER["REQUEST_URI"];
	  return htmlentities($url);
	}
	
	function securityCheck(){		
		if(empty($_POST)) return true;		
		if (! function_exists('wp_verify_nonce') ) return false;
		if ( !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],getClassVariable("referer_check_key")))
			return false;
		
		if ( function_exists('is_site_admin') && ! is_site_admin() )
			return false;
			
		return true;
	}
	
	function getClassVariable($variable_name){
		return $GLOBALS['class']->getVariable($variable_name);
	}
	
	function setClassVariable($variable_name,$variable_value){
		 $GLOBALS['class']->setVariable($variable_name,$variable_value);
	}
	/***************************************************************************************************************************/
	
?>