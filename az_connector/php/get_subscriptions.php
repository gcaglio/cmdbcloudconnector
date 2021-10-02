<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// outputs the username that owns the running php/httpd process
// // (on a system with the "whoami" executable in the path)
$output=null;
$retval=null;
$command="az account list --all --refresh 2>/dev/null";

exec($command, $output, $retval);
printCommandOutputDebug($retval,$output);

#echo "Returned with status $retval and output:\n";
#print_r(join($output));

$json_obj=json_decode(join($output),false);

$out_subs_filepath=$output_path."/".$out_subs_filename;
$f_subs_output = fopen($out_subs_filepath, "w") or die("Unable to open file : ".$out_subs_filepath);
fwrite($f_subs_output,"Code;Description;SubscriptionId;SubscriptionName;State;TenantId;Account\r\n");

for ($s=0; $s<count($json_obj); $s++){
  #var_dump($json_obj[0]);
  $id=$json_obj[$s]->{"id"};
  $hash_id=md5($id);
  $name=$json_obj[$s]->{"name"};
  $state=$json_obj[$s]->{"state"};
  $tenantId=$json_obj[$s]->{"tenantId"};
  $account=$json_obj[$s]->{"user"}->{"name"};

  $line=$hash_id.";".$name.";".$id.";".$name.";".$state.";".$tenantId.";".$account."\r\n";
  fwrite($f_subs_output,$line);
}	
fclose($f_subs_output);
?>
