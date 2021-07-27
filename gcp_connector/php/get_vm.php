<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all projects
// for each project get vm(s)
// for each vm get all disks

$prjs_output=null;
$prjs_retval=null;
$prjs_command="gcloud projects list --format=json 2>/dev/null";

exec($prjs_command, $prjs_output, $prjs_retval);

$prjs_json_obj=json_decode(join($prjs_output),false);

// output file for VM
$out_vm_filepath=$output_path."/".$out_vm_filename;
$f_vm_output = fopen($out_vm_filepath, "w") or die("Unable to open file : ".$out_vm_filepath);
fwrite($f_vm_output,"Code;Description;Id;Name;Zone;MachineType\r\n");

//output file for SUBS-VM
$out_rel_prjs_vm_filepath=$output_path."/".$out_rel_prjs_vm;
$f_rel_prjs_vm_output = fopen($out_rel_prjs_vm_filepath, "w") or die("Unable to open file : ".$out_rel_prjs_vm_filepath);
fwrite($f_rel_prjs_vm_output,"hash_ProjectId;hash_VmId\r\n");


//output file for BusinessAppLandscape-VM
//$out_rel_bal_vm_filepath=$output_path."/".$out_rel_busapplandscape_vm;
//$f_rel_bal_vm_output = fopen($out_rel_bal_vm_filepath, "w") or die("Unable to open file : ".$out_rel_bal_vm_filepath);
//fwrite($f_rel_bal_vm_output,"code_baLandscape;hash_VmId\r\n");


// cycle on all projects
for ($s=0; $s<count($prjs_json_obj); $s++){
  if ( $prjs_json_obj[$s]->{"lifecycleState"} === "ACTIVE" ){ 
    $prj_id=$prjs_json_obj[$s]->{"projectId"};	
    $hash_prjid=md5(strtolower($prj_id));	 

    echo "INFO : working on project : ".$prj_id."\r\n";

    $vm_output=null;
    $vm_retval=null;

    $vm_command="gcloud compute instances list --project \"".$prj_id. "\" --format=json --quiet";  

    exec($vm_command, $vm_output, $vm_retval);
    $vm_json_obj=json_decode(join($vm_output),false);
    //cycle on all VMs
    echo "INFO : found ".count($vm_json_obj)." VMs\r\n";
    for ($v=0; $v<count($vm_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $vm_id=$vm_json_obj[$v]->{"id"};
      $hash_vmid=md5(strtolower($vm_id));                 // this will be the CODE 32-byte length
      $vm_name=$vm_json_obj[$v]->{"name"};
      $vm_zone=$vm_json_obj[$v]->{"zone"};		  // format is URL/URI, get the last /
      $vm_zone=substr($vm_zone,strrpos($vm_zone,"/")+1);
      $vm_machineType=$vm_json_obj[$v]->{"machineType"};  // format is URL/URI, get the last /
      $vm_machineType=substr($vm_machineType,strrpos($vm_machineType,"/")+1);


      //$vm_resgroup=$vm_json_obj[$v]->{"resourceGroup"};


      // write line in VMs file
      $line=$hash_vmid.";".$vm_name.";".$vm_id.";".$vm_name.";".$vm_zone.";".$vm_machineType."\r\n";
      fwrite($f_vm_output, $line);

      // write line in rel-subs-vm file
      $rel_line=$hash_prjid.";".$hash_vmid."\r\n";
      fwrite($f_rel_prjs_vm_output,$rel_line);

      // get landscape from tag
      //$landscape="";
      //if (isset($vm_json_obj[$v]->{"tags"}->{$tag_landscape} )){
      //  $landscape=$vm_json_obj[$v]->{"tags"}->{$tag_landscape};
      //}

      // split businessApp, add lines in relation file
      //if (isset($vm_json_obj[$v]->{"tags"}->{$tag_appid})){
      //  $appids=explode($tag_appid_separator,$vm_json_obj[$v]->{"tags"}->{$tag_appid});
//	for ($t=0;$t<count($appids); $t++){
//	  if (strlen($appids[$t])>0){
 //           if (strlen($landscape)>0)
//	      $landscape="_".$landscape;
//
//	    $rel_line=$appids[$t].$landscape.";".$hash_vmid."\r\n";
//	    fwrite($f_rel_bal_vm_output,$rel_line);
//	  }
//	}
//      }


    }
  }
	
}	

fclose($f_vm_output);
fclose($f_rel_prjs_vm_output);
//fclose($f_rel_bal_vm_output);

?>
