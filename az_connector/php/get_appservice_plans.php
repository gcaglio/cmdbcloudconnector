<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get appsvcplans

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for appsvcplans
$out_appsvcplan_filepath=$output_path."/".$out_appsvcplan_filename;
$f_appsvcplan_output = fopen($out_appsvcplan_filepath, "w") or die("Unable to open file : ".$out_appsvcplan_filepath);
fwrite($f_appsvcplan_output,"Code;Description;Id;Name;Location;Sku;Kind;ResourceGroup\r\n");

//output file for SUBS-APPSVCPLAN
$out_rel_subs_appsvcplan_filepath=$output_path."/".$out_rel_subs_appsvcplan;
$f_rel_subs_appsvcplan_output = fopen($out_rel_subs_appsvcplan_filepath, "w") or die("Unable to open file : ".$out_rel_subs_appsvcplan_filepath);
fwrite($f_rel_subs_appsvcplan_output,"hash_SubsId;hash_AppSvcPlanId\r\n");


//output file for BusinessAppLandscape-VM
$out_rel_bal_appsvcplan_filepath=$output_path."/".$out_rel_busapplandscape_appsvcplan;
$f_rel_bal_appsvcplan_output = fopen($out_rel_bal_appsvcplan_filepath, "w") or die("Unable to open file : ".$out_rel_bal_appsvcplan_filepath);
fwrite($f_rel_bal_appsvcplan_output,"hash_baLandscape;hash_AppSvcPlanId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $plan_output=null;
    $plan_retval=null;

    $plan_command="az appservice plan list --subscription ".$subs_id;  
    exec($plan_command, $plan_output, $plan_retval);
    $plan_json_obj=json_decode(join($plan_output),false);
    //cycle on all VMs
    echo "INFO : found ".count($plan_json_obj)." App Service Plans\r\n";
    for ($v=0; $v<count($plan_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $plan_id=$plan_json_obj[$v]->{"id"};
      $hash_planid=md5(strtolower($plan_id));                 // this will be the CODE 32-byte length
      $plan_name=$plan_json_obj[$v]->{"name"};
      $plan_location=$plan_json_obj[$v]->{"location"};
      $plan_sku=$plan_json_obj[$v]->{"sku"}->{"size"};

//      $vm_avset="";
//      if ( isset($vm_json_obj[$v]->{"availabilitySet"}) ){
//       $vm_avset=$vm_json_obj[$v]->{"availabilitySet"}->{"id"};
//       $vm_avset=substr($vm_avset,strpos($vm_avset,"/availabilitySet")+17);
//      }
      $plan_resgroup=$plan_json_obj[$v]->{"resourceGroup"};
      $plan_kind=$plan_json_obj[$v]->{"kind"};

      // write line in AppSvcPlans file
      $line=$hash_planid.";".$plan_name.";".$plan_id.";".$plan_name.";".$plan_location.";".$plan_sku.";".$plan_kind.";".$plan_resgroup."\r\n";
      fwrite($f_appsvcplan_output, $line);

      // write line in rel-subs-appsvcplan file
      $rel_line=$hash_subsid.";".$hash_planid."\r\n";
      fwrite($f_rel_subs_appsvcplan_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($plan_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$plan_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($plan_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$plan_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
	    $rel_line=$appids[$t]."_".$landscape.";".$hash_planid."\r\n";
	    fwrite($f_rel_bal_appsvcplan_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_appsvcplan_output);
fclose($f_rel_subs_appsvcplan_output);
fclose($f_rel_bal_appsvcplan_output);

?>
