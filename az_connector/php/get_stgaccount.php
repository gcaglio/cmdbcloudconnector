<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get storage account(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for storage accounts
$out_stgaccount_filepath=$output_path."/".$out_stgaccount_filename;
$f_stgaccount_output = fopen($out_stgaccount_filepath, "w") or die("Unable to open file : ".$out_stgaccount_filepath);
fwrite($f_stgaccount_output,"Code;Id;Name;Location;PrimaryLocation;SecondaryLocation;CustomDomain;Sku;ResourceGroup\r\n");

//output file for BusinessAppLandscape-webapp
$out_rel_bal_stgaccount_filepath=$output_path."/".$out_rel_busapplandscape_stgaccount;
$f_rel_bal_stgaccount_output = fopen($out_rel_bal_stgaccount_filepath, "w") or die("Unable to open file : ".$out_rel_bal_stgaccount_filepath);
fwrite($f_rel_bal_stgaccount_output,"hash_baLandscape;hash_StgAccountId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $sa_output=null;
    $sa_retval=null;

    $sa_command="az storage account list --subscription ".$subs_id;  
    exec($sa_command, $sa_output, $sa_retval);
    printCommandOutputDebug($sa_command,$sa_output);

    $sa_json_obj=json_decode(join($sa_output),false);
    //cycle on all storage account
    echo "INFO : found ".count($sa_json_obj)." Storage accounts\r\n";
    for ($v=0; $v<count($sa_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $sa_id=$sa_json_obj[$v]->{"id"};
      $hash_said=md5(strtolower($sa_id));                 // this will be the CODE 32-byte length

      $sa_name=$sa_json_obj[$v]->{"name"};
      $sa_location=$sa_json_obj[$v]->{"location"};
      $sa_customDomain=$sa_json_obj[$v]->{"customDomain"};


      $sa_httpsOnly="false";
      if ($sa_json_obj[$v]->{"enableHttpsTrafficOnly"}) {
        $sa_httpsOnly=true;
      }

      $sa_nfsv3="false";
      if ($sa_json_obj[$v]->{"enableNfsV3"}) {
        $sa_nfsv3=true;
      }

      $sa_primaryLocation="";
      if (isset($sa_json_obj[$v]->{"primaryLocation"})) {
        $sa_primaryLocation=$sa_json_obj[$v]->{"primaryLocation"};
      }
      $sa_secondaryLocation="";
      if (isset($sa_json_obj[$v]->{"secondayLocation"})) {
        $sa_secondaryLocation=$sa_json_obj[$v]->{"secondaryLocation"};
      }
      

      $sa_resgroup=$sa_json_obj[$v]->{"resourceGroup"};

      $sa_sku=$sa_json_obj[$v]->{"sku"}->{"name"};

      // write line in webappss file
      $line=$hash_said.";".$sa_id.";".$sa_name.";".$sa_location.";".$sa_primaryLocation.";".$sa_secondaryLocation.";".$sa_customDomain.";".$sa_sku.";".$sa_resgroup."\r\n";
      fwrite($f_stgaccount_output, $line);


      // get landscape from tag
      $landscape="";
      if (isset($sa_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$sa_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($sa_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$sa_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
            if (strlen($landscape)>0)
	      $landscape="_".$landscape;	

	    $rel_line=$appids[$t].$landscape.";".$hash_said."\r\n";
	    fwrite($f_rel_bal_stgaccount_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_stgaccount_output);
fclose($f_rel_bal_stgaccount_output);

?>
