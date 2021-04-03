<?php
include "./conf/output.php";

// get all subscriptions
// for each enabled subscriptions get disk(s)

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for Disks
$out_disk_filepath=$output_path."/".$out_disks_filename;
$f_disk_output = fopen($out_disk_filepath, "w") or die("Unable to open file : ".$out_disk_filepath);
fwrite($f_disk_output,"Code;Description;Id;Name;Location;ManagedBy;EncryptionType;DiskState;DiskSizeGb;ResourceGroup\r\n");

//output file for Disk-VM
$out_rel_disk_vm_filepath=$output_path."/".$out_rel_disks_vm;
$f_rel_disk_vm_output = fopen($out_rel_disk_vm_filepath, "w") or die("Unable to open file : ".$out_rel_disk_vm_filepath);
fwrite($f_rel_disk_vm_output,"hash_DiskId;hash_VmId\r\n");

$out_rel_subs_disk_filepath=$output_path."/".$out_rel_subs_disk;
$f_rel_subs_disk_output = fopen($out_rel_subs_disk_filepath, "w") or die("Unable to open file : ".$out_rel_subs_disk_filepath);
fwrite($f_rel_subs_disk_output,"hash_SubsId;hash_DiskId\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $disk_output=null;
    $disk_retval=null;


    $disk_command="az disk list --subscription ".$subs_id;  
    exec($disk_command, $disk_output, $disk_retval);
    $disk_json_obj=json_decode(join($disk_output),false);
    //cycle on all VMs
    echo "INFO : found ".count($disk_json_obj)." Disks\r\n";
    for ($v=0; $v<count($disk_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $disk_id=$disk_json_obj[$v]->{"id"};
      $hash_diskid=md5(strtolower($disk_id));                 // this will be the CODE 32-byte length
      $disk_name=$disk_json_obj[$v]->{"name"};
      $disk_location=$disk_json_obj[$v]->{"location"};
      
      $disk_enctype="n/a";
      if (isset($disk_json_obj[$v]->{"encryption"} ) ) {
        $disk_enctype=$disk_json_obj[$v]->{"encryption"}->{"type"};
      }

      $disk_state=$disk_json_obj[$v]->{"diskState"};
      $disk_managedBy=$disk_json_obj[$v]->{"managedBy"};
      $hash_managedBy=md5(strtolower($disk_managedBy));
      $disk_sizeGB=$disk_json_obj[$v]->{"diskSizeGb"};

      //$vm_avset="";
      //if ( isset($vm_json_obj[$v]->{"availabilitySet"}) ){
      //  $vm_avset=$vm_json_obj[$v]->{"availabilitySet"}->{"id"};
      //  $vm_avset=substr($vm_avset,strpos($vm_avset,"/availabilitySet")+17);
      //}
      $disk_resgroup=$disk_json_obj[$v]->{"resourceGroup"};

      $line=$hash_diskid.";".$disk_name.";".$disk_id.";".$disk_name.";".$disk_location.";".$disk_managedBy.";".$disk_enctype.";".$disk_state.";".$disk_sizeGB.";".$disk_resgroup."\r\n";
      fwrite($f_disk_output, $line);



      // write line in rel disk-vm file
      $rel_line=$hash_diskid.";".$hash_managedBy."\r\n";
      fwrite($f_rel_disk_vm_output,$rel_line);

      // write line in rel subs-disk file
      $rel_line=$hash_subsid.";".$hash_diskid."\r\n";
      fwrite($f_rel_subs_disk_output,$rel_line);
    }

  }
	
}	

fclose($f_disk_output);
fclose($f_rel_disk_vm_output);
fclose($f_rel_subs_disk_output);

?>
