<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get sqlserver server
// for each sqlserver server get db(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for sql servers
$out_sqlsrv_filepath=$output_path."/".$out_sqlsrv_filename;
$f_sqlsrv_output = fopen($out_sqlsrv_filepath, "w") or die("Unable to open file : ".$out_sqlsrv_filepath);
fwrite($f_sqlsrv_output,"Code;Description;Id;Name;Location;FQDN;publicNetworkAccess;MinimalTlsVersion;Version;Type;ResourceGroup\r\n");

//output file for BusinessAppLandscape-servers
$out_rel_bal_sqlsrv_filepath=$output_path."/".$out_rel_busapplandscape_sqlsrv;
$f_rel_bal_sqlsrv_output = fopen($out_rel_bal_sqlsrv_filepath, "w") or die("Unable to open file : ".$out_rel_bal_sqlsrv_filepath);
fwrite($f_rel_bal_sqlsrv_output,"code_baLandscape;hash_SqlSrvId\r\n");


// output file for sql db
$out_sqldb_filepath=$output_path."/".$out_sqlsrvdb_filename;
$f_sqldb_output = fopen($out_sqldb_filepath, "w") or die("Unable to open file : ".$out_sqldb_filepath);
fwrite($f_sqldb_output,"Code;Description;Id;Name;Location;Collation;CatalogCollation;CreationDate;SkuName;SkuTier;BckStorageRedundancy;DefaultSecondaryLocation;Edition;ElasticPoolName;FailoverGroupID;HighAvailabilityReplicaCount;Kind;MaxLogSizeBytes;MaxSizeBytes;MinCapacity;ZoneRedundancy;ResourceGroup;Type\r\n");

//output file for BusinessAppLandscape-db
$out_rel_bal_sqldb_filepath=$output_path."/".$out_rel_busapplandscape_sqldb;
$f_rel_bal_sqldb_output = fopen($out_rel_bal_sqldb_filepath, "w") or die("Unable to open file : ".$out_rel_bal_sqldb_filepath);
fwrite($f_rel_bal_sqldb_output,"code_baLandscape;hash_SqlDbId\r\n");


//output file for sql server - db
$out_rel_sqlsrv_sqldb_filepath=$output_path."/".$out_rel_sqlsrv_sqldb;
$f_rel_sqlsrv_sqldb_output = fopen($out_rel_sqlsrv_sqldb_filepath, "w") or die("Unable to open file : ".$out_rel_sqlsrv_sqldb_filepath);
fwrite($f_rel_sqlsrv_sqldb_output,"hash_SqlSrvId;hash_SqlDbId\r\n");


//output file for subscription-sqlserver relation
$out_rel_subs_sqlsrv_filepath=$output_path."/".$out_rel_subs_sqlsrv;
$f_rel_subs_sqlsrv_output = fopen($out_rel_subs_sqlsrv_filepath, "w") or die("Unable to open file : ".$out_rel_subs_sqlsrv_filepath);
fwrite($f_rel_subs_sqlsrv_output,"hash_SubsId;hash_SqlSrvId\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $sqlsrv_output=null;
    $sqlsrv_retval=null;


    $sqlsrv_command="az sql server list --subscription ".$subs_id;  
    exec($sqlsrv_command, $sqlsrv_output, $sqlsrv_retval);
    printCommandOutputDebug($sqlsrv_command,$sqlsrv_output);

    $sqlsrv_json_obj=json_decode(join($sqlsrv_output),false);
    //cycle on all server
    echo "INFO : found ".count($sqlsrv_json_obj)." sqlserver\r\n";
    

    for ($v=0; $v<count($sqlsrv_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $sqlsrv_id=$sqlsrv_json_obj[$v]->{"id"};
      $hash_sqlsrvid=md5(strtolower($sqlsrv_id));                 // this will be the CODE 32-byte length

      $sqlsrv_name=$sqlsrv_json_obj[$v]->{"name"};
      $sqlsrv_location=$sqlsrv_json_obj[$v]->{"location"};
      $sqlsrv_fqdn=$sqlsrv_json_obj[$v]->{"fullyQualifiedDomainName"};

      $sqlsrv_pubnetaccess=$sqlsrv_json_obj[$v]->{"publicNetworkAccess"};
      $sqlsrv_version=$sqlsrv_json_obj[$v]->{"version"};
      $sqlsrv_type=$sqlsrv_json_obj[$v]->{"type"};
      $sqlsrv_minimaltlsversion=$sqlsrv_json_obj[$v]->{"minimalTlsVersion"};

      $sqlsrv_resgroup=$sqlsrv_json_obj[$v]->{"resourceGroup"};

      $line=$hash_sqlsrvid.";".$sqlsrv_name.";".$sqlsrv_id.";".$sqlsrv_name.";".$sqlsrv_location.";".$sqlsrv_fqdn.";".$sqlsrv_pubnetaccess.";".$sqlsrv_minimaltlsversion.";".$sqlsrv_version.";".$sqlsrv_type.";".$sqlsrv_resgroup."\r\n";
      fwrite($f_sqlsrv_output, $line);


      // get landscape from tag
      $landscape="";
      if (isset($sqlsrv_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$sqlsrv_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($sqlsrv_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$sqlsrv_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
	    if (strlen($landscape)>0)
	      $landscape="_".$landscape;

  	    $rel_line=$appids[$t].$landscape.";".$hash_sqlsrvid."\r\n";
	    fwrite($f_rel_bal_sqlsrv_output,$rel_line);
	  }
	}
      }

	
      // subscription-sqlserver relation
      $rel_sub_line=$hash_subsid.";".$hash_sqlsrvid."\r\n";
      fwrite($f_rel_subs_sqlsrv_output,$rel_sub_line);



      // get all DBs of this server
      $sqldb_output=null;
      $sqldb_retval=null;
      $sqldb_command="az sql db list --subscription ".$subs_id." --resource-group \"".$sqlsrv_resgroup."\" --server \"".$sqlsrv_name."\"";
      //echo $sqldb_command;
      exec($sqldb_command, $sqldb_output, $sqldb_retval);
      printCommandOutputDebug($sqldb_command,$sqldb_output);

      $sqldb_json_obj=json_decode(join($sqldb_output),false);
      //cycle on all server
      echo "INFO : found ".count($sqldb_json_obj)." sql server db databases on server ".$sqlsrv_name."\r\n";

      for ($x=0; $x<count($sqldb_json_obj); $x++){
        $sqldb_id=$sqldb_json_obj[$x]->{"id"};
        $hash_sqldbid=md5(strtolower($sqldb_id));                 // this will be the CODE 32-byte length

        $sqldb_name=$sqldb_json_obj[$x]->{"name"};
        $sqldb_bckstorageredundancy="";
        if (isset($sqldb_json_obj[$x]->{"backupStorageRedundancy"})) {
          $sqldb_bckstorageredundancy=$sqldb_json_obj[$x]->{"backupStorageRedundancy"};
        }
        $sqldb_catalogCollation=$sqldb_json_obj[$x]->{"catalogCollation"};
        $sqldb_collation=$sqldb_json_obj[$x]->{"collation"};
	$sqldb_createDate=$sqldb_json_obj[$x]->{"creationDate"};
	$sqldb_skuname=$sqldb_json_obj[$x]->{"currentSku"}->{"name"};
	$sqldb_skutier=$sqldb_json_obj[$x]->{"currentSku"}->{"tier"};
	$sqldb_defaultSecondaryLocation=$sqldb_json_obj[$x]->{"defaultSecondaryLocation"};
	$sqldb_edition=$sqldb_json_obj[$x]->{"edition"};
	$sqldb_elasticPoolName=$sqldb_json_obj[$x]->{"elasticPoolName"};
	$sqldb_failoverGroupId=$sqldb_json_obj[$x]->{"failoverGroupId"};

	$sqldb_highAvailabilityReplicaCount=0;
        if (isset($sqldb_json_obj[$x]->{"highAvailabilityReplicaCount"}) ){
          $sqldb_highAvailabilityReplicaCount=$sqldb_json_obj[$x]->{"highAvailabilityReplicaCount"};
        }

	$sqldb_kind=$sqldb_json_obj[$x]->{"kind"};
	$sqldb_location=$sqldb_json_obj[$x]->{"location"};
	$sqldb_maxLogSizeBytes=$sqldb_json_obj[$x]->{"maxLogSizeBytes"};
	$sqldb_maxSizeBytes=$sqldb_json_obj[$x]->{"maxSizeBytes"};
	$sqldb_minCapacity=$sqldb_json_obj[$x]->{"minCapacity"};
	$sqldb_zoneRedundant=$sqldb_json_obj[$x]->{"zoneRedundant"};
	$sqldb_resourceGroup=$sqldb_json_obj[$x]->{"resourceGroup"};
	$sqldb_type=$sqldb_json_obj[$x]->{"type"};


        //$mdb_charset=$mdbdb_json_obj[$x]->{"charset"};
	//$mdb_collation=$mdbdb_json_obj[$x]->{"collation"};


//	var_dump($sqldb_json_obj[$x]);

	$line_db=$hash_sqldbid.";".$sqldb_name.";".$sqldb_id.";".$sqldb_name.";".$sqldb_location.";".$sqldb_collation.";".$sqldb_catalogCollation.";".$sqldb_createDate.";".$sqldb_skuname.";".$sqldb_skutier.";".$sqldb_bckstorageredundancy.";".$sqldb_defaultSecondaryLocation.";".$sqldb_edition.";".$sqldb_elasticPoolName.";".$sqldb_failoverGroupId.";".$sqldb_highAvailabilityReplicaCount.";".$sqldb_kind.";".$sqldb_maxLogSizeBytes.";".$sqldb_maxSizeBytes.";".$sqldb_minCapacity.";".$sqldb_zoneRedundant.";".$sqldb_resourceGroup.";".$sqldb_type."\r\n";
	//echo $line_db;
	fwrite($f_sqldb_output, $line_db);


        // get landscape from tag
        $landscape_db="";
        if (isset($sqldb_json_obj[$x]->{"tags"}->{$tag_landscape} )){
          $landscape=$sqldb_json_obj[$x]->{"tags"}->{$tag_landscape};
        }

	#var_dump( $sqldb_json_obj[$v]->{"tags"}->{$tag_appid} );
        // split businessApp, add lines in relation file
	if (isset($sqldb_json_obj[$x]->{"tags"}->{$tag_appid})){
	  $appids=explode($tag_appid_separator,$sqldb_json_obj[$x]->{"tags"}->{$tag_appid});
          for ($t=0;$t<count($appids); $t++){
            if (strlen($appids[$t])>0){
              $temp_landscape="";
              if (strlen($landscape)>0)
                $temp_landscape="_".$landscape;

	      $rel_line_db=$appids[$t].$temp_landscape.";".$hash_sqldbid."\r\n";

              fwrite($f_rel_bal_sqldb_output,$rel_line_db);
            }
          }
        }

        $line_srv_id=$hash_sqlsrvid.";".$hash_sqldbid."\r\n";
	fwrite($f_rel_sqlsrv_sqldb_output,$line_srv_id);


      }


    }

  }
	
}	

fclose($f_sqlsrv_output);
fclose($f_rel_bal_sqlsrv_output);
fclose($f_sqldb_output);
fclose($f_rel_bal_sqldb_output);
fclose($f_rel_sqlsrv_sqldb_output);
fclose($f_rel_subs_sqlsrv_output);
?>
