<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get vm(s)
// for each vm get all disks

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for VM
$out_vm_filepath=$output_path."/".$out_vm_filename;
$f_vm_output = fopen($out_vm_filepath, "w") or die("Unable to open file : ".$out_vm_filepath);
fwrite($f_vm_output,"Code;Id;Name;Location;StandardFamily;AvailabilitySet;DiagnosticsProfile;ResourceGroup;Type;Zones;ImageExactVersion;ImageOffer;ImagePublisher;ImageSku;ImageVersion;ImageId\r\n");

//output file for SUBS-VM
$out_rel_subs_vm_filepath=$output_path."/".$out_rel_subs_vm;
$f_rel_subs_vm_output = fopen($out_rel_subs_vm_filepath, "w") or die("Unable to open file : ".$out_rel_subs_vm_filepath);
fwrite($f_rel_subs_vm_output,"hash_SubsId;hash_VmId\r\n");


//output file for BusinessAppLandscape-VM
$out_rel_bal_vm_filepath=$output_path."/".$out_rel_busapplandscape_vm;
$f_rel_bal_vm_output = fopen($out_rel_bal_vm_filepath, "w") or die("Unable to open file : ".$out_rel_bal_vm_filepath);
fwrite($f_rel_bal_vm_output,"code_baLandscape;hash_VmId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $vm_output=null;
    $vm_retval=null;

    $vm_command="az vm list --show-details --subscription ".$subs_id;  

    exec($vm_command, $vm_output, $vm_retval);
    printCommandOutputDebug($vm_command,$vm_output);
    $vm_json_obj=json_decode(join($vm_output),false);
    //cycle on all VMs
    echo "INFO : found ".count($vm_json_obj)." VMs\r\n";
    for ($v=0; $v<count($vm_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $vm_id=$vm_json_obj[$v]->{"id"};
      $hash_vmid=md5(strtolower($vm_id));                 // this will be the CODE 32-byte length
      $vm_name=$vm_json_obj[$v]->{"name"};
      $vm_location=$vm_json_obj[$v]->{"location"};
      $vm_stdfamily=$vm_json_obj[$v]->{"hardwareProfile"}->{"vmSize"};

      $vm_avset="";
      if ( isset($vm_json_obj[$v]->{"availabilitySet"}) ){
        $vm_avset=$vm_json_obj[$v]->{"availabilitySet"}->{"id"};
        $vm_avset=substr($vm_avset,strpos($vm_avset,"/availabilitySet")+17);
      }
      $vm_resgroup=$vm_json_obj[$v]->{"resourceGroup"};
      $vm_type=$vm_json_obj[$v]->{"type"};
      $vm_zones="";
      if ( isset($vm_json_obj[$v]->{"zones"}) ){
        for ($z=0; $z<count($vm_json_obj[$v]->{"zones"}); $z++){
          //echo $vm_json_obj[$v]->{"zones"}[$z];
          $vm_zones=$vm_json_obj[$v]->{"zones"}[$z]." ";  
	      }
      }

      $vm_diagprofile="";
      if ( isset($vm_json_obj[$v]->{"diagnosticProfile"}) ){
	if ( isset($vm_json_obj[$v]->{"availabilitySet"}->{"storageUri"}) ){
	  $vm_diagprofile=$vm_json_obj[$v]->{"availabilitySet"}->{"storageUri"};	      
	}
      }

      $vm_imageexactversion="";
      $vm_imageoffer="";
      $vm_imagepublisher="";
      $vm_imagesku="";
      $vm_imageid="";
      $vm_imageversion="";
      if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}) ){
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"exactVersion"}) ){
  	  $vm_imageexactversion=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"exactVersion"};
	}
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"offer"}) ){
          $vm_imageoffer=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"offer"};
	}
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"publisher"}) ){
          $vm_imagepublisher=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"publisher"};
	}
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"sku"}) ){
          $vm_imagesku=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"sku"};
	}
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"version"}) ){
          $vm_imageversion=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"version"};
	}
        if ( isset($vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"id"}) ){
          $vm_imageid=$vm_json_obj[$v]->{"storageProfile"}->{"imageReference"}->{"id"};
        }
      }


      // write line in VMs file
      $line=$hash_vmid.";".$vm_id.";".$vm_name.";".$vm_location.";".$vm_stdfamily.";".$vm_avset.";".$vm_diagprofile.";".$vm_resgroup.";".$vm_type.";".$vm_zones.";".$vm_imageexactversion.";".$vm_imageoffer.";".$vm_imagepublisher.";".$vm_imagesku.";".$vm_imageversion.";".$vm_imageid."\r\n";
      fwrite($f_vm_output, $line);

      // write line in rel-subs-vm file
      $rel_line=$hash_subsid.";".$hash_vmid."\r\n";
      fwrite($f_rel_subs_vm_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($vm_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$vm_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($vm_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$vm_json_obj[$v]->{"tags"}->{$tag_appid});
       	for ($t=0;$t<count($appids); $t++){
          if (strlen($appids[$t])>0){
	          $temp_landscape=$landscape;		  
            if (strlen($landscape)>0)
	            $temp_landscape="_".$landscape;

	          $rel_line=$appids[$t].$temp_landscape.";".$hash_vmid."\r\n";
	          fwrite($f_rel_bal_vm_output,$rel_line);
	        }
	      }
      }


    }

  }
	
}	

fclose($f_vm_output);
fclose($f_rel_subs_vm_output);
fclose($f_rel_bal_vm_output);

?>
