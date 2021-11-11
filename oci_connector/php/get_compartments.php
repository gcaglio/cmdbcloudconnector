<?php
include "./conf/output.php";
include "./utils/utils.php";

# get OCI compartments

$output=null;
$retval=null;
$command=$oci_path."/oci iam compartment list --all --compartment-id-in-subtree true --access-level ACCESSIBLE --include-root --raw-output 2>/dev/null";
exec($command, $output, $retval);
printCommandOutputDebug($command,$output);

$json_obj=json_decode(join($output),false);

$out_cmpt_filepath=$output_path."/".$out_cmpts_filename;
$f_cmpt_output = fopen($out_cmpt_filepath, "w") or die("Unable to open file : ".$out_cmpt_filepath);
fwrite($f_cmpt_output,"Code;Description;Name;Id;CompartmentId;IsAccessible;LifecyleState\r\n");


for ($s=0; isset($json_obj->{"data"}) &&  $s<count($json_obj->{"data"}); $s++){
  #var_dump($json_obj[0]);
  $id=$json_obj->{"data"}[$s]->{"id"};
  $cmpt_id="";
  if (isset($json_obj->{"data"}[$s]->{"compartment-id"})){
    $cmpt_id=$json_obj->{"data"}[$s]->{"compartment-id"};
  }
  $hash_id=md5($id);
  $name=$json_obj->{"data"}[$s]->{"name"};
  $description=$json_obj->{"data"}[$s]->{"description"};
  $lifecyle_state=$json_obj->{"data"}[$s]->{"lifecycle-state"};
  $is_accessible=$json_obj->{"data"}[$s]->{"is-accessible"};

  $line=$hash_id.";".$description.";".$name.";".$id.";".$cmpt_id.";".$is_accessible.";".$lifecyle_state."\r\n";
  fwrite($f_cmpt_output,$line);
}	
fclose($f_cmpt_output);
?>
