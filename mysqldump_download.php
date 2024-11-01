<?php
try{
	$dbname   = $_POST["dbname"];
	$dumpfname = $_POST["dumpfname"];
	$backup_folder = $_POST["backup_folder"];	
}catch (Exception $e){}

if(empty($backup_folder)){
	$backup_folder="backup/";
}
if (file_exists($dumpfname)) {		
	// zip the dump file	
	$name=$dbname . "_" . date("Y-m-d");	
	$zipfname = $backup_folder.$name.".zip";
	$zip = new ZipArchive();	
	if($zip->open($zipfname,ZIPARCHIVE::CREATE)) 
	{
	   $zip->addFile($dumpfname,$dumpfname);
	   $zip->close();
	}	
	// read zip file and send it to standard output
	if (file_exists($zipfname)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($zipfname));
		flush();
		readfile($zipfname);
		exit;
	}	
}
?>