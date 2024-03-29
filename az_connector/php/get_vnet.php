<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get vnet(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";


exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);


$subs_json_obj=json_decode(join($subs_output),false);

// output file for vnet
$out_vnet_filepath=$output_path."/".$out_vnet_filename;
$f_vnet_output = fopen($out_vnet_filepath, "w") or die("Unable to open file : ".$out_vnet_filepath);
fwrite($f_vnet_output,"Code;Id;Name;Location;AddressPrefixes;ResourceGroup\r\n");

//output file for BusinessAppLandscape-Vnet
$out_rel_bal_vnet_filepath=$output_path."/".$out_rel_busapplandscape_vnet;
$f_rel_bal_vnet_output = fopen($out_rel_bal_vnet_filepath, "w") or die("Unable to open file : ".$out_rel_bal_vnet_filepath);
fwrite($f_rel_bal_vnet_output,"code_baLandscape;hash_VnetId\r\n");


//output file for private ips
$out_privip_filepath=$output_path."/".$out_privip_filename;
$f_privip_output = fopen($out_privip_filepath, "w") or die("Unable to open file : ".$out_privip_filepath);
fwrite($f_privip_output,"Code;Id;Name;PrivateIp;SubnetName;VnetName;ResourceGroup\r\n");

$out_rel_privip_vnet_filepath=$output_path."/".$out_rel_privip_vnet;
$f_rel_privip_vnet_output = fopen($out_rel_privip_vnet_filepath, "w") or die("Unable to open file : ".$out_rel_privip_vnet_filepath);
fwrite($f_rel_privip_vnet_output,"code_Privip;code_Vnet\r\n");


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
    printCommandOutputDebug($vnet_command,$vnet_output);

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
      $line=$hash_vnetid.";".$vnet_id.";".$vnet_name.";".$vnet_location.";".$addrprfx.";".$vnet_resgroup."\r\n";
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
            if (strlen($landscape)>0)
              $landscape="_".$landscape;

	    $rel_line=$appids[$t].$landscape.";".$hash_vnetid."\r\n";
	    fwrite($f_rel_bal_vnet_output,$rel_line);
	  }
	}
      }

      // get connected ip addresses
      $vnetdtl_output=null;
      $vnetdtl_retval=null;
      $vnetdtl_command="az network vnet show --resource-group \"".$vnet_resgroup."\" --ids \"".$vnet_id."\" --expand \"subnets/ipConfigurations\" 2>/dev/null";
      printCommandOutputDebug($vnetdtl_command,$vnetdtl_output);


      exec($vnetdtl_command, $vnetdtl_output, $vnetdtl_retval);

      $vnetdtl_json_obj=json_decode(join($vnetdtl_output),false);
      //var_dump($vnetdtl_json_obj);
      for ($sn=0; $sn<count($vnetdtl_json_obj->{"subnets"}); $sn++){
        #var_dump($vnetdtl_json_obj->{"subnets"});
        $subnet_obj=$vnetdtl_json_obj->{"subnets"}[$sn];
        $subnet_id=$vnetdtl_json_obj->{"subnets"}[$sn]->{"id"};
        $subnet_name=$vnetdtl_json_obj->{"subnets"}[$sn]->{"name"};
        $hash_subnetid=md5(strtolower($vnetdtl_json_obj->{"subnets"}[$sn]->{"id"}));

        // scan all ip configurations to get PrivateIPs AND private endpoint
        for ($ic=0; $ic<count($subnet_obj->{"ipConfigurations"}); $ic++){
          $ipc_id=$subnet_obj->{"ipConfigurations"}[$ic]->{"id"};
          $hash_ipcid=md5(strtolower($ipc_id));
          $ipc_name=$subnet_obj->{"ipConfigurations"}[$ic]->{"name"};
	  $ipc_privIps=$subnet_obj->{"ipConfigurations"}[$ic]->{"privateIpAddress"};

          $ipc_resgroup=$subnet_obj->{"ipConfigurations"}[$ic]->{"resourceGroup"};
     
          $line_ips=$hash_ipcid.";".$ipc_id.";".$ipc_name.";".$ipc_privIps.";".$subnet_name.";".$vnet_name.";".$ipc_resgroup.";\r\n";
          fwrite($f_privip_output,$line_ips);

          $rel_line_ips_vnet=$hash_ipcid.";".$hash_vnetid.";\r\n";
	  fwrite($f_rel_privip_vnet_output,$rel_line_ips_vnet);
         
        }


      }  



    }

  }
	
}	

fclose($f_vnet_output);
//fclose($f_rel_subs_vm_output);
fclose($f_rel_bal_vnet_output);

?>
