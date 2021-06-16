<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get vnet(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for vnet
$out_vnet_filepath=$output_path."/".$out_vnet_filename;
$f_vnet_output = fopen($out_vnet_filepath, "w") or die("Unable to open file : ".$out_vnet_filepath);
fwrite($f_vnet_output,"Code;Description;Id;Name;Location;AddressPrefixes;ResourceGroup\r\n");

//output file for BusinessAppLandscape-Vnet
$out_rel_bal_vnet_filepath=$output_path."/".$out_rel_busapplandscape_vnet;
$f_rel_bal_vnet_output = fopen($out_rel_bal_vnet_filepath, "w") or die("Unable to open file : ".$out_rel_bal_vnet_filepath);
fwrite($f_rel_bal_vnet_output,"code_baLandscape;hash_VnetId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $vnet_output=null;
    $vnet_retval=null;

    $vnet_command="az network vnet list --subscription ".$subs_id;  
    exec($vnet_command, $vnet_output, $vnet_retval);
    $vnet_json_obj=json_decode(join($vnet_output),false);
    //cycle on all vnet
    echo "INFO : found ".count($vnet_json_obj)." vnet\r\n";
    for ($v=0; $v<count($vnet_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $vnet_id=$vnet_json_obj[$v]->{"id"};
      $hash_vnetid=md5(strtolower($vnet_id));                 // this will be the CODE 32-byte length
      $vnet_name=$vnet_json_obj[$v]->{"name"};
      $vnet_location=$vnet_json_obj[$v]->{"location"};
      $vnet_resgroup=$vnet_json_obj[$v]->{"resourceGroup"};

      $vnet_addrprfx=$vnet_json_obj[$v]->{"addressSpace"}->{"addressPrefixes"};
      $addrprfx="";
      for ($a=0; $a<count($vnet_addrprfx); $a++){ 
	if (is_array($vnet_addrprfx)){
	  for ($i=0; $i<count($vnet_addrprfx); $i++){	      
            $addrprfx=$vnet_addrprfx[$i].";"; 
	  }
	}else{
	  $addrprfx=$addrprfx.$vnet_addrprfx.";";
	}
      } 

      // write line in vnet file
      $line=$hash_vnetid.";".$vnet_name.";".$vnet_id.";".$vnet_name.";".$vnet_location.";".$addrprfx.";".$vnet_resgroup."\r\n";
      fwrite($f_vnet_output, $line);

      // write line in rel-subs-vm file
      //$rel_line=$hash_subsid.";".$hash_vmid."\r\n";
      //fwrite($f_rel_subs_vm_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($vnet_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$vnet_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($vnet_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$vnet_json_obj[$v]->{"tags"}->{$tag_appid});
	for ($t=0;$t<count($appids); $t++){
	  if (strlen($appids[$t])>0){
	    $rel_line=$appids[$t]."_".$landscape.";".$hash_vnetid."\r\n";
	    fwrite($f_rel_bal_vnet_output,$rel_line);
	  }
	}
      }


    }

  }
	
}	

fclose($f_vnet_output);
//fclose($f_rel_subs_vm_output);
fclose($f_rel_bal_vnet_output);

?>
