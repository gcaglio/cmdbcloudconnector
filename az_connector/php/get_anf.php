<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get azure netappfiles account
// for each azure netappfiles account get capacity pool
// for each capacity pool get volumes

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for azure netappfiles account
$out_anfaccount_filepath=$output_path."/".$out_anfaccount_filename;
$f_anfaccount_output = fopen($out_anfaccount_filepath, "w") or die("Unable to open file : ".$out_anfaccount_filepath);
fwrite($f_anfaccount_output,"Code;Id;Name;Location;ResourceGroup;Type\r\n");

// output file for anfvolumes
$out_anfvol_filepath=$output_path."/".$out_anfvol_filename;
$f_anfvol_output = fopen($out_anfvol_filepath, "w") or die("Unable to open file : ".$out_anfvol_filepath);
fwrite($f_anfvol_output,"Code;Id;Name;Size\r\n");

//output file for anfpools
$out_anfpool_filepath=$output_path."/".$out_anfpool_filename;
$f_anfpool_output = fopen($out_anfpool_filepath, "w") or die("Unable to open file : ".$out_anfpool_filepath);
fwrite($f_anfpool_output,"Code;Id;Name;ServiceLevl;QosType;PoolId;Size;ResourceGroup;Type\r\n");


// output file for rel subscription - anfaccount
$out_rel_subs_anfaccount_filepath=$output_path."/".$out_rel_subs_anfaccount;
$f_rel_subs_anfaccount_output = fopen($out_rel_subs_anfaccount_filepath, "w") or die("Unable to open file : ".$out_rel_subs_anfaccount_filepath);
fwrite($f_rel_subs_anfaccount_output,"hash_SubsId;hash_AnfAccountId\r\n");

// output file for rel pool - anfaccount
$out_rel_anfpool_anfaccount_filepath=$output_path."/".$out_rel_anfpool_anfaccount;
$f_rel_anfpool_anfaccount_output = fopen($out_rel_anfpool_anfaccount_filepath, "w") or die("Unable to open file : ".$out_rel_anfpool_anfaccount_filepath);
fwrite($f_rel_anfpool_anfaccount_output,"hash_AnfPoolId;hash_AnfAccountId\r\n");

// output file for rel pool - volume
$out_rel_anfpool_anfvol_filepath=$output_path."/".$out_rel_anfpool_anfvol;
$f_rel_anfpool_anfvol_output = fopen($out_rel_anfpool_anfvol_filepath, "w") or die("Unable to open file : ".$out_rel_anfpool_anfvol_filepath);
fwrite($f_rel_anfpool_anfvol_output,"hash_AnfPoolId;hash_AnfVolumeId\r\n");

//output file for busapplandscape-azure netappfiles volume
$out_rel_bal_anfvol_filepath=$output_path."/".$out_rel_busapplandscape_anfvol;
$f_rel_bal_anfvol_output = fopen($out_rel_bal_anfvol_filepath, "w") or die("Unable to open file : ".$out_rel_bal_anfvol_filepath);
fwrite($f_rel_bal_anfvol_output,"code_baLandscape;hash_AnfVolId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $anfacc_output=null;
    $anfacc_retval=null;


    $anfacc_command="az netappfiles account list --subscription \"".$subs_id."\"";  
    exec($anfacc_command, $anfacc_output, $anfacc_retval);
    printCommandOutputDebug($anfacc_command,$anfacc_output);

    $anfacc_json_obj=json_decode(join($anfacc_output),false);
    //cycle on all netappfiles account
    echo "INFO : found ".count($anfacc_json_obj)." NetappFiles (ANF) account.\r\n";
    

    for ($v=0; $v<count($anfacc_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $anfacc_id=$anfacc_json_obj[$v]->{"id"};
      $hash_anfaccid=md5(strtolower($anfacc_id));                 // this will be the CODE 32-byte length

      $anfacc_name=$anfacc_json_obj[$v]->{"name"};
      $anfacc_location=$anfacc_json_obj[$v]->{"location"};
      $anfacc_type=$anfacc_json_obj[$v]->{"type"};
      $anfacc_resgroup=$anfacc_json_obj[$v]->{"resourceGroup"};

      // write line in anfaccount file
      $line=$hash_anfaccid.";".$anfacc_id.";".$anfacc_name.";".$anfacc_location.";".$anfacc_resgroup.";".$anfacc_type."\r\n";
      fwrite($f_anfaccount_output, $line);


      // write subs-anfaccount relations
      $subs_anfaccount_line=$hash_subsid.";".$hash_anfaccid."\r\n";
      fwrite($f_rel_subs_anfaccount_output,$subs_anfaccount_line);

      // get all pool of this anf account
      $anfpool_output=null;
      $anfpool_retval=null;
      $anfpool_command="az netappfiles pool list --account-name \"".$anfacc_name."\" --subscription ".$subs_id." --resource-group \"".$anfacc_resgroup."\"";
      exec($anfpool_command, $anfpool_output, $anfpool_retval);
      printCommandOutputDebug($anfpool_command,$anfpool_output);

      $anfpool_json_obj=json_decode(join($anfpool_output),false);
      //cycle on all pool
      echo "INFO : found ".count($anfpool_json_obj)." capacity pool(s) on this account ".$anfacc_name."\r\n";

      for ($x=0; $x<count($anfpool_json_obj); $x++){
        $anfpool_id=$anfpool_json_obj[$x]->{"id"};
        $hash_anfpoolid=md5(strtolower($anfpool_id));                 // this will be the CODE 32-byte length

        $anfpool_name=$anfpool_json_obj[$x]->{"name"};
        $anfpool_qostype=$anfpool_json_obj[$x]->{"qosType"};
        $anfpool_size=$anfpool_json_obj[$x]->{"size"};
        $anfpool_resgroup=$anfpool_json_obj[$x]->{"resourceGroup"};
        $anfpool_servicelevel=$anfpool_json_obj[$x]->{"serviceLevel"};
        $anfpool_poolId=$anfpool_json_obj[$x]->{"poolId"};
        $anfpool_type=$anfpool_json_obj[$x]->{"type"};

	$line_pool=$hash_anfpoolid.";".$anfpool_id.";".$anfpool_name.";".$anfpool_qostype.";".$anfpool_servicelevel.";".$anfpool_poolId.";".$anfpool_size.";".$anfpool_resgroup.";".$anfpool_type."\r\n";
	fwrite($f_anfpool_output, $line_pool);

	$anfpool_anfacc_line=$hash_anfpoolid.";".$hash_anfaccid."\r\n";
	fwrite($f_rel_anfpool_anfaccount_output,$anfpool_anfacc_line);



	// get all volumes of this anf pool
	$poolname=substr($anfpool_name,strpos($anfpool_name,"/")+1);
        $anfvol_output=null;
        $anfvol_retval=null;
        $anfvol_command="az netappfiles volume list --pool-name \"".$poolname."\" --account-name \"".$anfacc_name."\" --subscription ".$subs_id." --resource-group \"".$anfacc_resgroup."\"";
        exec($anfvol_command, $anfvol_output, $anfvol_retval);
        printCommandOutputDebug($anfvol_command,$anfvol_output);

        $anfvol_json_obj=json_decode(join($anfvol_output),false);
        //cycle on all volumes
        echo "INFO : found ".count($anfvol_json_obj)." volume(s) on this account ".$anfacc_name."\r\n";

        for ($z=0; $z<count($anfvol_json_obj); $z++){
          $anfvol_id=$anfvol_json_obj[$z]->{"id"};
          $hash_anfvolid=md5(strtolower($anfvol_id));                 // this will be the CODE 32-byte length

          $anfvol_name=$anfvol_json_obj[$z]->{"name"};
          $anfvol_baremetalTenantId=$anfvol_json_obj[$z]->{"baremetalTenantId"};
          $anfvol_location=$anfvol_json_obj[$z]->{"location"};
          $anfvol_subnetId=$anfvol_json_obj[$z]->{"subnetId"};
          $anfvol_serviceLevel=$anfvol_json_obj[$z]->{"serviceLevel"};
          $anfvol_type=$anfvol_json_obj[$z]->{"type"};
          $anfvol_size=$anfvol_json_obj[$z]->{"usageThreshold"};
          $anfvol_tput=$anfvol_json_obj[$z]->{"throughputMibps"};

          $line_vol=$hash_anfvolid.";".$anfvol_id.";".$anfvol_name.";".$anfvol_baremetalTenantId.";".$anfvol_location.";".$anfvol_subnetId.";".$anfvol_size.";".$anfvol_serviceLevel.";".$anfvol_tput.";".$anfvol_type."\r\n";
          fwrite($f_anfvol_output, $line_vol);

          $anfpool_anfvol_line=$hash_anfpoolid.";".$hash_anfvolid."\r\n";
          fwrite($f_rel_anfpool_anfvol_output,$anfpool_anfvol_line);


          // get landscape from tag
	  $landscape="";

	  //if (isset($anfvol_json_obj[$x]->{"tags"}->{$tag_landscape} )){
	  if ( array_key_exists($tag_landscape,$anfvol_json_obj[$x]->{"tags"}) ){
            $landscape=$anfvol_json_obj[$x]->{"tags"}->{$tag_landscape};
          }

          // split businessApp, add lines in relation file
          if (isset($anfvol_json_obj[$v]->{"tags"}->{$tag_appid})){
            $appids=explode($tag_appid_separator,$anfvol_json_obj[$v]->{"tags"}->{$tag_appid});
            for ($t=0;$t<count($appids); $t++){
              if (strlen($appids[$t])>0){
                if (strlen($landscape)>0)
                  $landscape="_".$landscape;

                $rel_line_anfvol=$appids[$t].$landscape.";".$hash_anfvolid."\r\n";
                fwrite($f_rel_bal_anfvol_output,$rel_line_anfvol);
              }
            }
          }

        }

      }


    }

  }
	
}	

fclose($f_anfaccout_output);
fclose($f_anfpool_output);
fclose($f_anfvol_output);
fclose($f_rel_bal_anfvol_output);
fclose($f_rel_anfpool_anfaccount_output);
fclose($f_rel_anfpool_anfvol_output);
fclose($f_rel_subs_anfaccount_output);
?>
