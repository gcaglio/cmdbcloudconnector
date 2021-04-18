<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get webapp(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for webapp
$out_webapp_filepath=$output_path."/".$out_webapp_filename;
$f_webapp_output = fopen($out_webapp_filepath, "w") or die("Unable to open file : ".$out_webapp_filepath);
fwrite($f_webapp_output,"Code;Description;Id;Name;Location;DefaultHostname;EnabledHostnames;HttpsOnly;LastModifiedTimeUtc;PossibleOutboundIpAddresses;ResourceGroup\r\n");

//output file for APPSVCPLAN-webapp
$out_rel_appsvcplan_webapp_filepath=$output_path."/".$out_rel_appsvcplan_webapp;
$f_rel_appsvcplan_webapp_output = fopen($out_rel_appsvcplan_webapp_filepath, "w") or die("Unable to open file : ".$out_rel_appsvcplan_webapp_filepath);
fwrite($f_rel_appsvcplan_webapp_output,"hash_AppSvcPlanId;hash_WebappId\r\n");


//output file for BusinessAppLandscape-webapp
$out_rel_bal_webapp_filepath=$output_path."/".$out_rel_busapplandscape_webapp;
$f_rel_bal_webapp_output = fopen($out_rel_bal_webapp_filepath, "w") or die("Unable to open file : ".$out_rel_bal_webapp_filepath);
fwrite($f_rel_bal_webapp_output,"hash_baLandscape;hash_WebappId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $wa_output=null;
    $wa_retval=null;

    $wa_command="az webapp list --subscription ".$subs_id;  
    exec($wa_command, $wa_output, $wa_retval);
    $wa_json_obj=json_decode(join($wa_output),false);
    //cycle on all webapps
    echo "INFO : found ".count($wa_json_obj)." WebApps\r\n";
    for ($v=0; $v<count($wa_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $wa_id=$wa_json_obj[$v]->{"id"};
      $hash_waid=md5(strtolower($wa_id));                 // this will be the CODE 32-byte length
      $wa_svcplan=$wa_json_obj[$v]->{"appServicePlanId"};
      $hash_svcplanid=md5(strtolower($wa_svcplan));                 // this will be the CODE 32-byte length for appsvc-webapp relation

      $wa_name=$wa_json_obj[$v]->{"name"};
      $wa_location=$wa_json_obj[$v]->{"location"};
      $wa_defaultHostname=$wa_json_obj[$v]->{"defaultHostName"};

      $wa_httpsOnly="false";
      if ($wa_json_obj[$v]->{"httpsOnly"}) {
        $wa_httpsOnly=true;
      }
      $wa_defaultHostname=$wa_json_obj[$v]->{"defaultHostName"};
      $wa_lastModifiedTime=$wa_json_obj[$v]->{"lastModifiedTimeUtc"};


      $wa_enabledHostnames="";
      for ($h=0; $h<count($wa_json_obj[$v]->{"enabledHostNames"}); $h++){
	$wa_enabledHostnames.=$wa_json_obj[$v]->{"enabledHostNames"}[$h]." ";
      }
      
      $wa_possibleOutboundIps=$wa_json_obj[$v]->{"possibleOutboundIpAddresses"};
      //for ($h=0; $h<count($wa_json_obj[$v]->{"possibleOutboundIpAddresses"}); $h++){
      //  $wa_possibleOutboundIps.=$wa_json_obj[$v]->{"possibleOutboundIpAddresses"}[$h]." ";
      //}

      $wa_resgroup=$wa_json_obj[$v]->{"resourceGroup"};


      // write line in webappss file
      $line=$hash_waid.";".$wa_name.";".$wa_id.";".$wa_name.";".$wa_location.";".$wa_defaultHostname.";".$wa_enabledHostnames.";".$wa_httpsOnly.";".$wa_lastModifiedTime.";".$wa_possibleOutboundIps.";".$wa_resgroup."\r\n";
      fwrite($f_webapp_output, $line);



      // write line in rel-appsvcplan-webapp file
      $rel_line=$hash_svcplanid.";".$hash_waid."\r\n";
      fwrite($f_rel_appsvcplan_webapp_output,$rel_line);



      // get landscape from tag
      $landscape="";
      if (isset($wa_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$wa_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($wa_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$wa_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
	    $rel_line=$appids[$t]."_".$landscape.";".$hash_waid."\r\n";
	    fwrite($f_rel_bal_webapp_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_webapp_output);
fclose($f_rel_appsvcplan_webapp_output);
fclose($f_rel_bal_webapp_output);

?>
