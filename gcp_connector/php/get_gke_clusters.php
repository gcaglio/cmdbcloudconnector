<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all projects
// for each project get gke(s) clusters

$prjs_output=null;
$prjs_retval=null;
$prjs_command="gcloud projects list --format=json 2>/dev/null";


exec($prjs_command, $prjs_output, $prjs_retval);
printCommandOutputDebug($prjs_command,$prjs_output);

$prjs_json_obj=json_decode(join($prjs_output),false);

// output file for gke
$out_gkec_filepath=$output_path."/".$out_gkec_filename;
$f_gkec_output = fopen($out_gkec_filepath, "w") or die("Unable to open file : ".$out_gkec_filepath);
fwrite($f_gkec_output,"Code;Id;Name;Description;Zone;ServiceIpv4Cidr;ClusterIpv4Cidr;Network;Subnetwork;CurrentMasterVersion;CurrentNodeVersion;CurrentNodeCount;Endpoint;SvcAccount;Status;Kind\r\n");

//output file for PRJ-GKEC
$out_rel_prjs_gkec_filepath=$output_path."/".$out_rel_prjs_gkec;
$f_rel_prjs_gkec_output = fopen($out_rel_prjs_gkec_filepath, "w") or die("Unable to open file : ".$out_rel_prjs_gkec_filepath);
fwrite($f_rel_prjs_gkec_output,"hash_ProjectId;hash_GkeClusterId\r\n");

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

    $gkec_output=null;
    $gkec_retval=null;

    $gkec_command="gcloud container clusters list --project \"".$prj_id. "\" --format=json --quiet";  

    exec($gkec_command, $gkec_output, $gkec_retval);
    printCommandOutputDebug($gkec_command,$gkec_output);

    $gkec_json_obj=json_decode(join($gkec_output),false);
    //cycle on all VMs
    echo "INFO : found ".count($gkec_json_obj)." VMs\r\n";
    for ($v=0; $v<count($gkec_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $gkec_id=$gkec_json_obj[$v]->{"id"};
      
      $gkec_selflink=$gkec_json_obj[$v]->{"selfLink"};
      $hash_gkecid=md5(strtolower($gkec_selflink));                 // this will be the CODE 32-byte length

      $gkec_name=$gkec_json_obj[$v]->{"name"};
      $gkec_description="";
      if (isset($gkec_json_obj[$v]->{"description"})){
	      $gkec_description=$gkec_json_obj[$v]->{"description"};
      }

      $gkec_zone=$gkec_json_obj[$v]->{"zone"};		  // format is URL/URI, get the last /
      if (strpos($gkec_zone,"/")>2){
	      $gkec_zone=substr($gkec_zone,strrpos($gkec_zone,"/")+1);
      }

      $gkec_kind="compute#gkecluster"; # is not present in the output
      $gkec_serviceipv4cidr=$gkec_json_obj[$v]->{"servicesIpv4Cidr"};
      $gkec_status=$gkec_json_obj[$v]->{"status"};
      $gkec_clusteripv4cidr=$gkec_json_obj[$v]->{"clusterIpv4Cidr"};
      $gkec_currentmasterversion=$gkec_json_obj[$v]->{"currentMasterVersion"};
      $gkec_currentnodeversion=$gkec_json_obj[$v]->{"currentNodeVersion"};
      $gkec_currentnodecount=$gkec_json_obj[$v]->{"currentNodeCount"};
      $gkec_endpoint=$gkec_json_obj[$v]->{"endpoint"};
      $gkec_network=$gkec_json_obj[$v]->{"network"};
      $gkec_subnetwork=$gkec_json_obj[$v]->{"subnetwork"};
      $gkec_svcaccount=$gkec_json_obj[$v]->{"svcAccount"};
      
      // write line in VMs file
      $line=$hash_gkecid.";".$gkec_id.";".$gkec_name.";".$gkec_description.";".$gkec_zone.";".$gkec_serviceipv4cidr.";".$gkec_clusteripv4cidr.";".$gkec_network.";".$gkec_subnetwork.";".$gkec_currentmasterversion.";".$gkec_currentnodeversion.";".$gkec_currentnodecount.";".$gkec_endpoint.";".$gkec_svcaccount.";".$gkec_status.";".$gkec_kind."\r\n";
      fwrite($f_gkec_output, $line);

      // write line in rel-subs-vm file
      $rel_line=$hash_prjid.";".$hash_gkecid."\r\n";
      fwrite($f_rel_prjs_gkec_output,$rel_line);


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

fclose($f_gkec_output);
fclose($f_rel_prjs_gkec_output);
//fclose($f_rel_bal_vm_output);

?>
