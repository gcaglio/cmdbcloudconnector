<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get service bus

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for cosmosdb
$out_svcbus_filepath=$output_path."/".$out_svcbus_filename;
$f_svcbus_output = fopen($out_svcbus_filepath, "w") or die("Unable to open file : ".$out_svcbus_filepath);
fwrite($f_svcbus_output,"Code;Description;Id;Name;Endpoint;Location;SkuTier;SkuName;SkuCapacity;ZoneRedundant;ResourceGroup\r\n");

//output file for BusinessAppLandscape-svcbus
$out_rel_bal_svcbus_filepath=$output_path."/".$out_rel_busapplandscape_svcbus;
$f_rel_bal_svcbus_output = fopen($out_rel_bal_svcbus_filepath, "w") or die("Unable to open file : ".$out_rel_bal_svcbus_filepath);
fwrite($f_rel_bal_svcbus_output,"code_baLandscape;hash_SvcBusId\r\n");

//output file for subs-svcbus
$out_rel_subs_svcbus_filepath=$output_path."/".$out_rel_subs_svcbus;
$f_rel_subs_svcbus_output = fopen($out_rel_subs_svcbus_filepath, "w") or die("Unable to open file : ".$out_rel_subs_svcbus_filepath);
fwrite($f_rel_subs_svcbus_output,"hash_SubsId;hash_SvcBusId\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $sbus_output=null;
    $sbus_retval=null;

    $sbus_command="az servicebus namespace list --subscription ".$subs_id;  
    exec($sbus_command, $sbus_output, $sbus_retval);
    $sbus_json_obj=json_decode(join($sbus_output),false);
    //cycle on all service bus (es)
    echo "INFO : found ".count($sbus_json_obj)." servicebus(es)\r\n";
    for ($v=0; $v<count($sbus_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $sbus_id=$sbus_json_obj[$v]->{"id"};
      $hash_sbusid=md5(strtolower($sbus_id));                 // this will be the CODE 32-byte length
      $sbus_name=$sbus_json_obj[$v]->{"name"};
      $sbus_resgroup=$sbus_json_obj[$v]->{"resourceGroup"};
      $sbus_endpoint=$sbus_json_obj[$v]->{"serviceBusEndpoint"};
      $sbus_zoneredundant="false";
      if ($sbus_json_obj[$v]->{"zoneRedundant"}==true){
        $sbus_zoneredundant="true";
      }

      $sbus_location=$sbus_json_obj[$v]->{"location"};

      $sku_tier=$sbus_json_obj[$v]->{"sku"}->{"tier"};
      $sku_name=$sbus_json_obj[$v]->{"sku"}->{"name"};
      $sku_capacity=$sbus_json_obj[$v]->{"sku"}->{"capacity"};

      // write line in servicebus
      $line=$hash_sbusid.";".$sbus_name.";".$sbus_id.";".$sbus_name.";".$sbus_endpoint.";".$sbus_location.";".$sku_tier.";".$sku_name.";".$sku_capacity.";".$sbus_zoneredundant.";".$sbus_resgroup."\r\n";
      fwrite($f_svcbus_output, $line);

      // write line in rel-subs-svcbus file
      $rel_line=$hash_subsid.";".$hash_sbusid."\r\n";
      fwrite($f_rel_subs_svcbus_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($sbus_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$sbus_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($sbus_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$sbus_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){

            if (strlen($landscape)>0)
	      $landscape="_".$landscape;

	    $rel_line=$appids[$t].$landscape.";".$hash_sbusid."\r\n";
	    fwrite($f_rel_bal_svcbus_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_svcbus_output);
fclose($f_rel_subs_svcbus_output);
fclose($f_rel_bal_svcbus_output);

?>
