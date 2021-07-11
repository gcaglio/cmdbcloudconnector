<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get cosmosdb

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for cosmosdb
$out_cosmos_filepath=$output_path."/".$out_cosmos_filename;
$f_cosmos_output = fopen($out_cosmos_filepath, "w") or die("Unable to open file : ".$out_cosmos_filepath);
fwrite($f_cosmos_output,"Code;Description;Id;Name;Location;ResourceGroup\r\n");

//output file for BusinessAppLandscape-cosmosdb
$out_rel_bal_cosmos_filepath=$output_path."/".$out_rel_busapplandscape_cosmos;
$f_rel_bal_cosmos_output = fopen($out_rel_bal_cosmos_filepath, "w") or die("Unable to open file : ".$out_rel_bal_cosmos_filepath);
fwrite($f_rel_bal_cosmos_output,"code_baLandscape;hash_CosmosDbId\r\n");

//output file for subs-cosmosdb
$out_rel_subs_cosmos_filepath=$output_path."/".$out_rel_subs_cosmos;
$f_rel_subs_cosmos_output = fopen($out_rel_subs_cosmos_filepath, "w") or die("Unable to open file : ".$out_rel_subs_cosmos_filepath);
fwrite($f_rel_subs_cosmos_output,"hash_SubsId;hash_CosmosDbId\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $cdb_output=null;
    $cdb_retval=null;

    $cdb_command="az cosmosdb list --subscription ".$subs_id;  
    exec($cdb_command, $cdb_output, $cdb_retval);
    $cdb_json_obj=json_decode(join($cdb_output),false);
    //cycle on all cosmosdb
    echo "INFO : found ".count($cdb_json_obj)." cosmosdb\r\n";
    for ($v=0; $v<count($cdb_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $cdb_id=$cdb_json_obj[$v]->{"id"};
      $hash_cdbid=md5(strtolower($cdb_id));                 // this will be the CODE 32-byte length
      $cdb_name=$cdb_json_obj[$v]->{"name"};
      $cdb_location=$cdb_json_obj[$v]->{"location"};
      $cdb_resgroup=$cdb_json_obj[$v]->{"resourceGroup"};

      // write line in cosmos file
      $line=$hash_cdbid.";".$cdb_name.";".$cdb_id.";".$cdb_name.";".$cdb_location.";".$cdb_resgroup."\r\n";
      fwrite($f_cosmos_output, $line);

      // write line in rel-subs-cosmos file
      $rel_line=$hash_subsid.";".$hash_cdbid."\r\n";
      fwrite($f_rel_subs_cosmos_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($vnet_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$vnet_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($cdb_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$cdb_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){

            if (strlen($landscape)>0)
	      $landscape="_".$landscape;

	    $rel_line=$appids[$t].$landscape.";".$hash_cdbid."\r\n";
	    fwrite($f_rel_bal_cosmos_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_cosmos_output);
fclose($f_rel_subs_cosmos_output);
fclose($f_rel_bal_cosmos_output);

?>
