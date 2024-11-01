<?php  		

	if ( !$GLOBAL['authorize'] ) die ("Direct access not permitted");	
		
	include('functions.php'); 	
	init();
	$security_check=true;	
	
	if(!isset($_POST['renaming_hidden'])){
		$_POST['renaming_hidden']='N';
	}

	$form_token='';	
	if($_POST['renaming_hidden'] != 'Y'){
		$form_token = uniqid();

		/*** add the form token to the session ***/
		$_SESSION['form_token'] = $form_token;		
	}else{
		if(isset($_POST['form_token'])){
			if($_POST['form_token'] != $_SESSION['form_token'])
			{
				setClassVariable("message",'Access denied!!Double form submission.');
				setClassVariable("message_type",$error);				
				$security_check=false;
			}
		}
	}	
	
	$mysql_location_start=getClassVariable("mysql_location_start");
	$dbuser=getClassVariable("dbuser");
	$dbpass=getClassVariable("dbpass");
	$dbhost=getClassVariable("dbhost");
	$dbname=getClassVariable("dbname");	
	$error="error";
	$success="success";
	$new_db_name=getClassVariable("new__db_name");					
	$new_folder_location=getClassVariable("new_folder_location");
	$new_url_location=getClassVariable("new_url_location");
	$old_url_location=getClassVariable("old_url_location");
	$dump_db_to_file=getClassVariable("dump_db_to_file");
	$drop_previous_wordpress=getClassVariable("drop_previous_wordpress");	
	$backup_folder=getClassVariable("backup_folder");			
	$drop_previous_database=getClassVariable("drop_previous_database");		
	$wordpress_root=getClassVariable("wordpress_root");	
	$renamed=getClassVariable("renamed");	
	$server_uri=$_SERVER['REQUEST_URI'];
	$database_backup_dump=getClassVariable("database_backup_dump");
	$backup_location_path=getClassVariable("backup_location_path");	
	
	/************************************SECURITY CHECK***************************************/
	
	
	if(empty($new_db_name) && empty($new_url_location) && $_POST['renaming_hidden'] == 'Y'){
		setClassVariable("message",'Please select new wordpress name or new wordpress database name.');
		setClassVariable("message_type",$error);				
		$security_check=false;	
	}
	
	if ( ! defined('ABSPATH') ) {
		setClassVariable("message",'Please do not load this file directly.');
		setClassVariable("message_type",$error);				
		$security_check=false;		
	}
	if(!$mysql_location_start || !file_exists($mysql_location_start)){
		if(!empty($database_backup_dump)){
			setClassVariable("message",'Please enter the correct mysql bin folder location.');
			setClassVariable("message_type",$error);		
			$security_check=false;		
		}
	}
	
	if($security_check && !securityCheck() && $_POST['renaming_hidden'] == 'Y'){
		setClassVariable("message",'Security validation failed!');
		setClassVariable("message_type",$error);		
		$security_check=false;		
	}
	/************************************SECURITY CHECK***************************************/
	
	$plugin_root="../wp-content/plugins/".getClassVariable("plugin_name");
	$action_path=$plugin_root."/mysqldump_download.php";
	$image_root=$plugin_root."/images/";
	$css_root=$plugin_root."/css/";
	$js_root=$plugin_root."/js/";
	
	try{	
			wp_enqueue_script("jquery");
			?>
				<link rel="stylesheet" href="<?php echo $css_root;?>main.css" type="text/css">				
				<script src="<?php echo $js_root;?>main.js"></script>				
			<?php
			if($security_check){
			
			if($_POST['renaming_hidden'] == 'Y') {  
				//Form data sent  
				//validate input parameters			
						
				$validated_parm = TRUE;
				
				if(($dbname != DB_NAME) || ($dbuser != DB_USER)|| ($dbpass != DB_PASSWORD)){
					$link = @mysql_connect($dbhost, $dbuser, $dbpass);
					if($link){
						$validated_parm = FALSE;
					}			
				}		
				if($validated_parm){
					
					$db= @mysql_select_db($dbname);
					
					if($db){					
						if(!empty($new_db_name)){
							//use case 1 - rename DB					
							
							//step 1: backup DB to file
							$GLOBALS['dumpfname']=backupDatabase($dump_db_to_file,$dbhost,$dbuser,$dbpass,$dbname,$backup_folder,$mysql_location_start,$database_backup_dump,$backup_location_path);
							
							//step 2: create new DB with new name
							createNewDatabase($new_db_name,$dbname,$mysql_location_start,$dbuser,$dbpass);
							
							//step 3: import previous DB to the new DB from dump file
							importDatabaseFromFile($GLOBALS['dumpfname'],$new_db_name,$dbhost,$dbuser,$dbpass,$mysql_location_start);
							
							//step 4: change wp-config.php to the new database
							change_wp_config($wordpress_root,$dbhost,$dbuser,$dbpass,$new_db_name);
							
							//step 5: if selected, drop previous database
							if(($drop_previous_database) == 'Y'){
								dropDatabase($drop_previous_database,$dbname);
							}
							
							//step 6: if selected, clean dump files
							if(empty($dump_db_to_file)){
								removeUnnecessaryFiles($dump_db_to_file,$GLOBALS['dumpfname'],"");
							}						
						}					
						if(!empty($new_url_location)){
							//use case 2 - rename Wordpress
							
							//step 1: backup database
							if(empty($new_db_name)){
								$GLOBALS['dumpfname']=backupDatabase($dump_db_to_file,$dbhost,$dbuser,$dbpass,$dbname,$backup_folder,$mysql_location_start,$database_backup_dump,$backup_location_path);
							}
							//step 2: rename wordpress in dump file
							$GLOBALS['dumpfname_renamed']=renameInFile($GLOBALS['dumpfname'],$old_url_location,$new_url_location,$dump_db_to_file,$dbhost,$dbuser,$dbpass,$dbname,$backup_folder,$mysql_location_start,$renamed);	
																				
							//step 3: import renamed database to the old or new database
							$db_import=$dbname;
							if(!empty($new_db_name)){
								//new
								$db_import=$new_db_name;						
							}
							//$GLOBALS['dumpfname_renamed']=str_replace($old_url_location,$new_url_location,$GLOBALS['dumpfname_renamed']);							
							importDatabaseFromFile($GLOBALS['dumpfname_renamed'],$db_import,$dbhost,$dbuser,$dbpass,$mysql_location_start);														
							
							try{
								//step 4: copy wordpress folder
								if(!empty($drop_previous_wordpress)){
									renameFolder($wordpress_root,$old_url_location,$new_url_location);
								}else{
									copyFolder($wordpress_root,$old_url_location,$new_url_location);
								}
							}catch(Exception $e){
								importDatabaseFromFile($GLOBALS['dumpfname'],$db_import,$dbhost,$dbuser,$dbpass,$mysql_location_start);
								throw new Exception($e->getMessage());
							}
							
							//step 5: change wordpress url and site url values							
							change_wordpress_url($dbhost,$new_url_location);								
							
							//step 6: clean files if necessary
							if(empty($dump_db_to_file)){
								removeUnnecessaryFiles($dump_db_to_file,$GLOBALS['dumpfname'],$GLOBALS['dumpfname_renamed']);
							}							
												
							//step 7: redirect to the new wordpress location				
							$url=str_replace($old_url_location,$new_url_location,getUrl());
							if (!headers_sent($filename, $linenum)) {
								header('Location: $url');
								exit;

							// You would most likely trigger an error here.
							} else {
								?>  
									<div class=$success><p><strong>Wordpress instance has been updated, please click this <a href="<?php echo($url) ?>">link</a></strong></p></div>  
								<?php						
								exit;
							}					
						}	
						setClassVariable("message"," New name saved.");
						setClassVariable("message_type",$success);
						if(!empty($new_db_name)){
							$dbname=$new_db_name;
						}
					}else{
						setClassVariable("message",'Login failed! Please verify database credentials!');
						setClassVariable("message_type",$error);					
						
					}		
					
				}else{
					setClassVariable("message",'Login failed! Please verify database credentials!');
					setClassVariable("message_type",$error);			
					
				}
				
			} 
		}
	}catch(Exception $e){
		setClassVariable("message",'Procedure failed!</br>Message: '.$e->getMessage());
		setClassVariable("message_type",$error);					
	}
?>

<div class="wrap">  
	 
    <?php    echo "<h2>" . __( 'Wordpress Rename', 'renaming_trdom' ) . "</h2>"; ?> 
	<?php
		//just before showing message, get latest value
		$message_type_str=getClassVariable("message_type");
		$message_str=getClassVariable("message");	
	?>
	<div id="message" class="<?php _e($message_type_str); ?>"><p><strong><?php _e($message_str); ?></strong></p>
	<?php
		/***************************************************************************************************************************************************/
				
		//backup db to file
		if(($dump_db_to_file == "Y") && ($message_type_str == $success)){
			?>
				<form name="backup_form" action="<?php echo $action_path; ?>" method="post">
					<fieldset>
						<input type="hidden" name="dbname" value="<?php echo $dbname; ?>" >						
					</fieldset>
			<?php						
				if(!empty($GLOBALS['dumpfname'])){
				?>
					<fieldset>
						<input type="hidden" name="dumpfname" value="<?php echo $GLOBALS['dumpfname']; ?>" >
						<input type="hidden" name="backup_folder" value="<?php echo $backup_folder; ?>" >
					</fieldset>
				<?php
				}else{
				?>
					<fieldset>
						<input type="hidden" name="dbhost" value="<?php echo $dbhost; ?>" >							
						<input type="hidden" name="dbuser" value="<?php echo $dbuser; ?>" >
						<input type="hidden" name="dbpass" value="<?php echo $dbpass; ?>" >
					</fieldset>
				<?php
				}
				?>							
					<a class="download_link" href="javascript: submitform()"><img src="<?php echo $image_root;?>download.png" alt="Download backup dump file" /><span>Download backup dump file</span></a>
				</form>					
			<?php					
		}		
		/***************************************************************************************************************************************************/	
	?>
	</div>
      
    <form id="renaming_form" name="renaming_form" method="post" action="<?php echo str_replace( '%7E', '~', $server_uri); ?>"> 	
		<fieldset>	
			<input type="hidden" name="renaming_hidden" value="Y">
			<input type="hidden" name="form_token" value="<?php echo $form_token; ?>" />
		<?php if ( function_exists('wp_nonce_field') ){
				wp_nonce_field(getClassVariable("referer_check_key")); 
			}
		?>
		</fieldset>
		<fieldset>
			<legend><?php    echo __( 'Wordpress Database Verification', 'renaming_trdom' ); ?></legend>
			<ol>
			<li><label for="host">Database host:</label><input type="text" autofocus placeholder="localhost" name="renaming_dbhost" value="<?php echo $dbhost; ?>" size="25"></li> 
			<li><label for="name">Database name:</label><input type="text" placeholder="wordpress" name="renaming_dbname" value="<?php echo $dbname; ?>" size="25"></li> 
			<li><label for="user">Database user:</label><input type="text" placeholder="root" autocomplete="off" name="renaming_dbuser" value="<?php echo $dbuser; ?>" size="25"></li>
			<li><label for="password">Database password:</label><input type="password" placeholder="secret password" autocomplete="off" name="renaming_dbpass" value="<?php echo $dbpass; ?>" size="25"></li>
			</ol>
			<hr /> 
		</fieldset>	        
		<fieldset>
			<legend><?php    echo  __( 'Wordpress New URL Location', 'renaming_trdom' ); ?></legend>
			<ol>
			<li><label for="old_url_location">OLD Wordress url location:</label><input type="text" readonly name="renaming_old_url_location" value="<?php echo getWordpressLocationUrl(); ?>" size="25"><?php getWordpressLocationUrl(); ?></li>  
			<li><label for="new_url_location">NEW Wordress url location:</label><input type="text" placeholder="Wordpress_name_new" name="renaming_new_url_location" value="" size="25"></li>   
			<div class="checkbox_wrapper">
				<input type="checkbox" value="Y" checked="checked"  name="drop_previous_wordpress"/> 
				<label>Delete previous wordpress folder location</label><br/>
			</div>			
			<div class="message_checkbox" style="display:none;">
				<p>NOTE: Procedure much slower. Previous wordpress location may contain a lot of files.</p>
			</div>
		
			</ol>
		</fieldset>		
		<fieldset>
			<legend><?php    echo __( 'Wordpress New DB name', 'renaming_trdom' ); ?></legend>
			<ol>
			<li><label for="old__db_name">OLD Wordress DB name: </label><input type="text" readonly name="renaming_old__db_name" value="<?php echo $dbname; ?>" size="25"><?php $dbname; ?></li>  
			<li><label for="new_db__name">NEW Wordress DB name:</label><input type="text" placeholder="Wordpress_db_name_new" name="renaming_new_db__name" value="" size="25"></li>  		
			<div class="checkbox_wrapper">
			<input type="checkbox" value="Y" checked="checked"  name="dump_db_to_file"/>
			<label>Backup database to a dump file</label><br/>
			</div>	
			<div class="checkbox_wrapper">
			<input type="checkbox" value="Y" checked="checked"  name="use_mysqldump"/>
			<label>Use MySQL dump</label><br/>
			</div>	
			<div class="message_mysqldump" style="display:none;">
				<p>NOTE: MySQL dump is necessary part of the procedure, but you can backup the database manually.</p>
				<p><?php _e("Backup location path: " ); ?><input type="text" name="backup_location_path" value="" size="40"></p>  
			</div>
			<div class="checkbox_wrapper">			
			<input type="checkbox" value="Y" name="drop_previous_database"/>
			<label>Drop previous database</label><br/>		
			</div>	
			</ol>
		</fieldset>		
		
        <p class="submit">  
        <input type="submit" id="rename_submit" name="Submit" value="<?php _e('Update Name', 'renaming_trdom' ) ?>" /> 
		<input type="button" onClick="history.go(0)" value="Refresh" />		
        </p> 		
    </form>  	
	<div class="divMsg" style="display:none;">
			<img src="<?php echo $image_root;?>ajax_loader_gray_512.gif" alt="Please wait.." />
	</div>
	
</div> 