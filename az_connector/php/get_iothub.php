<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get iothub

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for cosmosdb
$out_iothub_filepath=$output_path."/".$out_iothub_filename;
$f_iothub_output = fopen($out_iothub_filepath, "w") or die("Unable to open file : ".$out_iothub_filepath);
fwrite($f_iothub_output,"Code;Id;Name;PrimaryLocation;SecondaryLocation;Hostname;SkuTier;SkuName;SkuCapacity;ResourceGroup\r\n");

//output file for BusinessAppLandscape-iothub
$out_rel_bal_iothub_filepath=$output_path."/".$out_rel_busapplandscape_iothub;
$f_rel_bal_iothub_output = fopen($out_rel_bal_iothub_filepath, "w") or die("Unable to open file : ".$out_rel_bal_iothub_filepath);
fwrite($f_rel_bal_iothub_output,"code_baLandscape;hash_IotHubId\r\n");

//output file for subs-iothub
$out_rel_subs_iothub_filepath=$output_path."/".$out_rel_subs_iothub;
$f_rel_subs_iothub_output = fopen($out_rel_subs_iothub_filepath, "w") or die("Unable to open file : ".$out_rel_subs_iothub_filepath);
fwrite($f_rel_subs_iothub_output,"hash_SubsId;hash_IotHubId\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $ioth_output=null;
    $ioth_retval=null;

    $ioth_command="az iot hub list --subscription ".$subs_id;  
    exec($ioth_command, $ioth_output, $ioth_retval);
    printCommandOutputDebug($ioth_command,$ioth_output);

    $ioth_json_obj=json_decode(join($ioth_output),false);
    //cycle on all iothub
    echo "INFO : found ".count($ioth_json_obj)." iothub(s)\r\n";
    for ($v=0; $v<count($ioth_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $ioth_id=$ioth_json_obj[$v]->{"id"};
      $hash_iothid=md5(strtolower($ioth_id));                 // this will be the CODE 32-byte length
      $ioth_name=$ioth_json_obj[$v]->{"name"};
      $ioth_resgroup=$ioth_json_obj[$v]->{"resourcegroup"};
      $ioth_hostname=$ioth_json_obj[$v]->{"properties"}->{"hostName"};

      $ioth_location_p="";
      $ioth_location_s="";
      $ioth_locations=$ioth_json_obj[$v]->{"properties"}->{"locations"};
      for ($l=0;$l<count($ioth_locations); $l++){
        if ($ioth_locations[$l]->{"role"} == "primary" )
	  $ioth_location_p=$ioth_locations[$l]->{"location"};
	
	if ($ioth_locations[$l]->{"role"} == "secondary" )
	  $ioth_location_s=$ioth_locations[$l]->{"location"};
      }

      $sku_tier=$ioth_json_obj[$v]->{"sku"}->{"tier"};
      $sku_name=$ioth_json_obj[$v]->{"sku"}->{"name"};
      $sku_capacity=$ioth_json_obj[$v]->{"sku"}->{"capacity"};

      // write line in iothub file
      $line=$hash_iothid.";".$ioth_id.";".$ioth_name.";".$ioth_location_p.";".$ioth_location_s.";".$ioth_hostname.";".$sku_tier.";".$sku_name.";".$sku_capacity.";".$ioth_resgroup."\r\n";
      fwrite($f_iothub_output, $line);

      // write line in rel-subs-iothub file
      $rel_line=$hash_subsid.";".$hash_iothid."\r\n";
      fwrite($f_rel_subs_iothub_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($ioth_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$ioth_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($ioth_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$ioth_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){

            if (strlen($landscape)>0)
	      $landscape="_".$landscape;

	    $rel_line=$appids[$t].$landscape.";".$hash_iothid."\r\n";
	    fwrite($f_rel_bal_iothub_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_iothub_output);
fclose($f_rel_subs_iothub_output);
fclose($f_rel_bal_iothub_output);

?>
