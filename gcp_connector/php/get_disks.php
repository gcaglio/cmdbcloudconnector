<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all projects
// for each project get compute disks

$prjs_output=null;
$prjs_retval=null;
$prjs_command="gcloud projects list --format=json 2>/dev/null";

exec($prjs_command, $prjs_output, $prjs_retval);
printCommandOutputDebug($prjs_command,$prjs_output);

$prjs_json_obj=json_decode(join($prjs_output),false);

// output file for disks
$out_disks_filepath=$output_path."/".$out_disks_filename;
$f_disks_output = fopen($out_disks_filepath, "w") or die("Unable to open file : ".$out_disks_filepath);
fwrite($f_disks_output,"Code;Id;Name;BlockSize;SizeGb;SourceImage;Users;Kind\r\n");


//output file for PRJS-DISKS
$out_rel_prjs_disks_filepath=$output_path."/".$out_rel_prjs_disks;
$f_rel_prjs_disks_output = fopen($out_rel_prjs_disks_filepath, "w") or die("Unable to open file : ".$out_rel_prjs_disks_filepath);
fwrite($f_rel_prjs_disks_output,"hash_ProjectId;hash_DiskId\r\n");

//output file for VM-DISKS
$out_rel_disks_vm_filepath=$output_path."/".$out_rel_disks_vm;
$f_rel_disks_vm_output = fopen($out_rel_disks_vm_filepath, "w") or die("Unable to open file : ".$out_rel_disks_vm_filepath);
fwrite($f_rel_disks_vm_output,"hash_DisksId;hash_VmId\r\n");

// cycle on all projects
for ($s=0; $s<count($prjs_json_obj); $s++){
  if ( $prjs_json_obj[$s]->{"lifecycleState"} === "ACTIVE" ){ 
    $prj_id=$prjs_json_obj[$s]->{"projectId"};	
    $hash_prjid=md5(strtolower($prj_id));	 

    echo "INFO : working on project : ".$prj_id."\r\n";

    $disks_output=null;
    $disks_retval=null;

    $disks_command="gcloud compute disks list --project \"".$prj_id. "\" --format=json --quiet";  

    exec($disks_command, $disks_output, $disks_retval);
    printCommandOutputDebug($disks_command,$disks_output);

    $disks_json_obj=json_decode(join($disks_output),false);
    //cycle on all Disks
    echo "INFO : found ".count($disks_json_obj)." Disks\r\n";
    for ($v=0; $v<count($disks_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $disk_id=$disks_json_obj[$v]->{"id"};
      $disk_selflink=$disks_json_obj[$v]->{"selfLink"};
      $hash_diskid=md5(strtolower($disk_selflink));                 // this will be the CODE 32-byte length
      $disk_name=$disks_json_obj[$v]->{"name"};
      $disk_blocksize=$disks_json_obj[$v]->{"physicalBlockSizeBytes"};
      $disk_size=$disks_json_obj[$v]->{"sizeGb"};		
      $disk_sourceimg=$disks_json_obj[$v]->{"sourceImage"};       // format is URL/URI
      $disk_kind=$disks_json_obj[$v]->{"kind"};

      //$vm_resgroup=$vm_json_obj[$v]->{"resourceGroup"};

      $disk_users="";
      if ( isset($disks_json_obj[$v]->{"users"}) ){
        for ( $i=0; $i<count($disks_json_obj[$v]->{"users"}); $i++){
	  $disk_user=$disks_json_obj[$v]->{"users"}[$i];
	  $hash_diskuser=md5(strtolower($disk_user));
	  $disk_users.=$disk_user;

	  if ($i+1<count($disks_json_obj[$v]->{"users"})){
	    $disk_users.=",";
	  }

	  // write relation file since i'm already cycling on all users of the disk
	  $rel_disk_vm_line=$hash_diskid.";".$hash_diskuser;
	  fwrite($f_rel_disks_vm_output,$rel_disk_vm_line);
	}
      }

      // write line in VMs file
      $line=$hash_diskid.";".$disk_id.";".$disk_name.";".$disk_blocksize.";".$disk_size.";".$disk_sourceimg.";".$disk_users.";".$disk_kind."\r\n";
      fwrite($f_disks_output, $line);

      // write line in rel-prjs-disk file
      $rel_line=$hash_prjid.";".$hash_diskid."\r\n";
      fwrite($f_rel_prjs_disks_output,$rel_line);

      

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

fclose($f_disks_output);
fclose($f_rel_prjs_disks_output);
fclose($f_rel_disks_vm_output);

?>
