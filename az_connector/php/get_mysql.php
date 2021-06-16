<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get maridb server
// for each mysql server get db(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for mysql servers
$out_mysqlsrv_filepath=$output_path."/".$out_mysqlsrv_filename;
$f_mysqlsrv_output = fopen($out_mysqlsrv_filepath, "w") or die("Unable to open file : ".$out_mysqlsrv_filepath);
fwrite($f_mysqlsrv_output,"Code;Description;Id;Name;Location;FQDN;InfrastructureEncryption;publicNetworkAccess;SkuCapacity;SkuName;SkuTier;SslEnforcement;MinimalTlsVersion;BackupRetentionDays;GeoRedundantBackup;StorageAutogrow;StorageMb;Version;Type;ResourceGroup\r\n");

//output file for BusinessAppLandscape-servers
$out_rel_bal_mysqlsrv_filepath=$output_path."/".$out_rel_busapplandscape_mysqlsrv;
$f_rel_bal_mysqlsrv_output = fopen($out_rel_bal_mysqlsrv_filepath, "w") or die("Unable to open file : ".$out_rel_bal_mysqlsrv_filepath);
fwrite($f_rel_bal_mysqlsrv_output,"code_baLandscape;hash_MysqlSrvId\r\n");


// output file for mysql db
$out_mysqldb_filepath=$output_path."/".$out_mysqldb_filename;
$f_mysqldb_output = fopen($out_mysqldb_filepath, "w") or die("Unable to open file : ".$out_mysqldb_filepath);
fwrite($f_mysqldb_output,"Code;Description;Id;Name;Charset;Collation\r\n");

//output file for BusinessAppLandscape-db
$out_rel_bal_mysqldb_filepath=$output_path."/".$out_rel_busapplandscape_mysqldb;
$f_rel_bal_mysqldb_output = fopen($out_rel_bal_mysqldb_filepath, "w") or die("Unable to open file : ".$out_rel_bal_mysqldb_filepath);
fwrite($f_rel_bal_mysqldb_output,"code_baLandscape;hash_MysqlDbId\r\n");


//output file for mysql server - mysql db
$out_rel_mysqlsrv_mysqldb_filepath=$output_path."/".$out_rel_mysqlsrv_mysqldb;
$f_rel_mysqlsrv_mysqldb_output = fopen($out_rel_mysqlsrv_mysqldb_filepath, "w") or die("Unable to open file : ".$out_rel_mysqlsrv_mysqldb_filepath);
fwrite($f_rel_mysqlsrv_mysqldb_output,"hash_MysqlSrvId;hash_MysqlDbId\r\n");



// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $mdbsrv_output=null;
    $mdbsrv_retval=null;


    $mdbsrv_command="az mysql server list --subscription ".$subs_id;  
    exec($mdbsrv_command, $mdbsrv_output, $mdbsrv_retval);
    $mdbsrv_json_obj=json_decode(join($mdbsrv_output),false);
    //cycle on all server
    echo "INFO : found ".count($mdbsrv_json_obj)." mysqldb server\r\n";
    

    for ($v=0; $v<count($mdbsrv_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $msrv_id=$mdbsrv_json_obj[$v]->{"id"};
      $hash_msrvid=md5(strtolower($msrv_id));                 // this will be the CODE 32-byte length

      $msrv_name=$mdbsrv_json_obj[$v]->{"name"};
      $msrv_location=$mdbsrv_json_obj[$v]->{"location"};
      $msrv_fqdn=$mdbsrv_json_obj[$v]->{"fullyQualifiedDomainName"};

      $msrv_pubnetaccess=$mdbsrv_json_obj[$v]->{"publicNetworkAccess"};
      $msrv_version=$mdbsrv_json_obj[$v]->{"version"};
      $msrv_type=$mdbsrv_json_obj[$v]->{"type"};
      $msrv_sslenforcement=$mdbsrv_json_obj[$v]->{"sslEnforcement"};
      $msrv_minimaltlsversion=$mdbsrv_json_obj[$v]->{"minimalTlsVersion"};
      $msrv_infrastructureencryption=$mdbsrv_json_obj[$v]->{"infrastructureEncryption"};

      $msrv_resgroup=$mdbsrv_json_obj[$v]->{"resourceGroup"};


      $msrv_sku_cap=$mdbsrv_json_obj[$v]->{"sku"}->{"capacity"};
      $msrv_sku_tier=$mdbsrv_json_obj[$v]->{"sku"}->{"tier"};
      $msrv_sku_name=$mdbsrv_json_obj[$v]->{"sku"}->{"name"};

      $msrv_sp_backupdays=$mdbsrv_json_obj[$v]->{"storageProfile"}->{"backupRetentionDays"};
      $msrv_sp_geobackup=$mdbsrv_json_obj[$v]->{"storageProfile"}->{"geoRedundantBackup"};
      $msrv_sp_autogrow=$mdbsrv_json_obj[$v]->{"storageProfile"}->{"storageAutogrow"};
      $msrv_sp_size=$mdbsrv_json_obj[$v]->{"storageProfile"}->{"storageMb"};

      // write line in webappss file
      // "Code;Description;Id;Name;Location;FQDN;InfrastructureEncryption;publicNetworkAccess;SkuCapacity;SkuName;SkuTier;SslEnforcement;MinimalTlsVersion;BackupRetentionDays;GeoRedundantBackup;StorageAutogrow;StorageMb;Version;Type;ResourceGroup\r\n"
      
      $line=$hash_msrvid.";".$msrv_name.";".$msrv_id.";".$msrv_name.";".$msrv_location.";".$msrv_fqdn.";".$msrv_infrastructureencryption.";".$msrv_pubnetaccess.";".$msrv_sku_cap.";".$msrv_sku_name.";".$msrv_sku_tier.";".$msrv_sslenforcement.";".$msrv_minimaltlsversion.";".$msrv_sp_backupdays.";".$msrv_sp_geobackup.";".$msrv_sp_autogrow.";".$msrv_sp_size.";".$msrv_version.";".$msrv_type.";".$msrv_resgroup."\r\n";
      fwrite($f_mysqlsrv_output, $line);


      // get landscape from tag
      $landscape="";
      if (isset($mdbsrv_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$mdbsrv_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($mdbsrv_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$mdbsrv_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
	    if (strlen($landscape)>0)
	      $landscape="_".$landscape;

  	    $rel_line=$appids[$t].$landscape.";".$hash_msrvid."\r\n";
	    fwrite($f_rel_bal_mysqlsrv_output,$rel_line);
	  }
	}
      }


      // get all DBs of this server
      $mdbdb_output=null;
      $mdbdb_retval=null;
      $mdbdb_command="az mysql db list --subscription ".$subs_id." --resource-group \"".$msrv_resgroup."\" --server-name \"".$msrv_name."\"";
      exec($mdbdb_command, $mdbdb_output, $mdbdb_retval);
      $mdbdb_json_obj=json_decode(join($mdbdb_output),false);
      //cycle on all server
      echo "INFO : found ".count($mdbdb_json_obj)." mysqldb databases on server ".$msrv_name."\r\n";

      for ($x=0; $x<count($mdbdb_json_obj); $x++){
        $mdb_id=$mdbdb_json_obj[$x]->{"id"};
        $hash_mdbid=md5(strtolower($mdb_id));                 // this will be the CODE 32-byte length

        $mdb_name=$mdbdb_json_obj[$x]->{"name"};
        $mdb_charset=$mdbdb_json_obj[$x]->{"charset"};
	$mdb_collation=$mdbdb_json_obj[$x]->{"collation"};

	$line_db=$hash_mdbid.";".$mdb_name.";".$mdb_id.";".$mdb_name.";".$mdb_charset.";".$mdb_collation."\r\n";
	fwrite($f_mysqldb_output, $line_db);


        // get landscape from tag
        $landscape_db="";
        if (isset($mdbdb_json_obj[$x]->{"tags"}->{$tag_landscape} )){
          $landscape=$mdbdb_json_obj[$x]->{"tags"}->{$tag_landscape};
        }

        // split businessApp, add lines in relation file
        if (isset($mdbdb_json_obj[$v]->{"tags"}->{$tag_appid})){
          $appids=explode($tag_appid_separator,$mdbdb_json_obj[$v]->{"tags"}->{$tag_appid});
          for ($t=0;$t<count($appids); $t++){
            if (strlen($appids[$t])>0){
              if (strlen($landscape)>0)
                $landscape="_".$landscape;

              $rel_line_db=$appids[$t].$landscape.";".$hash_mdbid."\r\n";
              fwrite($f_rel_bal_mysqldb_output,$rel_line_db);
            }
          }
        }

        $line_srv_id=$hash_msrvid.";".$hash_mdbid."\r\n";
	fwrite($f_rel_mysqlsrv_mysqldb_output,$line_srv_id);


      }



    }

  }
	
}	

fclose($f_mysqlsrv_output);
fclose($f_rel_bal_mysqlsrv_output);
fclose($f_mysqldb_output);
fclose($f_rel_bal_mysqldb_output);
fclose($f_rel_mysqlsrv_mysqldb_output);

?>
