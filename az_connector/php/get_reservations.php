<?php
include "./conf/output.php";
include "./conf/tags.php";

// get all subscriptions
// for each enabled subscriptions get reservation orders
// for each reservation orders get reservation

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for mysql servers
$out_reserv_filepath=$output_path."/".$out_reserv_filename;
$f_reserv_output = fopen($out_reserv_filepath, "w") or die("Unable to open file : ".$out_reserv_filepath);
fwrite($f_reserv_output,"Code;Description;Id;AzName;Name;Location;Scope;Type;Quantity;SkuName;SkuDescription;Flexibility;Autorenew;BillingScope;Billing;Expiration;Archived;ExtendedStatusCode;ProvisioningState\r\n");

// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){
    $subs_id=$subs_json_obj[$s]->{"id"};
    $hash_subsid=md5(strtolower($subs_id));

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $ro_output=null;
    $ro_retval=null;


    $ro_command="az reservations reservation-order-id  list --subscription-id ".$subs_id;
    exec($ro_command, $ro_output, $ro_retval);
    $ro_json_obj=json_decode(join($ro_output),false);
    //cycle on all reservation-orders
    echo "INFO : found ".count($ro_json_obj)." reservation orders\r\n";

    for ($v=0; isset($ro_json_obj->{"reservationOrderIds"}->{"value"}) && $v<count($ro_json_obj->{"reservationOrderIds"}->{"value"}); $v++){
      #var_dump($reserv_json_obj[$v]);
      $ro_id=$ro_json_obj->{"reservationOrderIds"}->{"value"}[$v];
      $hash_roid=md5(strtolower($ro_id));                 // this will be the CODE 32-byte length
      echo "INFO : working on reservation order id :".$ro_id."\r\n";

      $ri_output=null;
      $ri_retval=null;

      $ro_id2=substr($ro_id,strrpos($ro_id,"/")+1);
      $ri_command="az reservations reservation  list --reservation-order-id \"".$ro_id2."\"";
      //echo $ri_command;
      exec($ri_command, $ri_output, $ri_retval);
      $ri_json_obj=json_decode(join($ri_output),false);
      //cycle on all reservation
      echo "INFO : found ".count($ri_json_obj)." reservation\r\n";

      for ($r=0;  $r<count($ri_json_obj);  $r++){
        $res_id=$ri_json_obj[$r]->{"id"};
        $hash_resid=md5(strtolower($res_id));

        echo "INFO : working on reservation id :".$res_id."\r\n";
        //var_dump($ri_json_obj[$r]);


        $res_expiratiot=$ri_json_obj[$r]->{"properties"}->{"expiryDate"};
        $res_location=$ri_json_obj[$r]->{"location"};
        $res_name=$ri_json_obj[$r]->{"name"};
        $res_scope=$ri_json_obj[$r]->{"properties"}->{"billingScopeId"};
        $res_displayname=$ri_json_obj[$r]->{"properties"}->{"displayName"};
        $res_type=$ri_json_obj[$r]->{"properties"}->{"reservedResourceType"};
        $res_qty=$ri_json_obj[$r]->{"properties"}->{"quantity"};
        $res_archived=$ri_json_obj[$r]->{"properties"}->{"archived"};
        $res_skuname=$ri_json_obj[$r]->{"sku"}->{"name"};
        $res_skudescription=$ri_json_obj[$r]->{"properties"}->{"skuDescription"};
        $res_flexi=$ri_json_obj[$r]->{"properties"}->{"instanceFlexibility"};
        $res_autorenew=$ri_json_obj[$r]->{"properties"}->{"renew"};
        $res_billingscope=$ri_json_obj[$r]->{"properties"}->{"billingScopeId"};
        $res_billing=$ri_json_obj[$r]->{"properties"}->{"billingPlan"};
        $res_expiration=$ri_json_obj[$r]->{"properties"}->{"expiryDate"};
        $res_provisioningstate=$ri_json_obj[$r]->{"properties"}->{"provisioningState"};

        $res_extendedStatus="";
        if (isset( $ri_json_obj[$r]->{"properties"}->{"extendedStatusInfo"} )) {
          $res_extendedStatus=$ri_json_obj[$r]->{"properties"}->{"extendedStatusInfo"}->{"statusCode"};
        }

        $line=$hash_resid.";".$res_displayname.";".$res_id.";".$res_name.";".$res_displayname.";".$res_location.";".$res_scope.";".$res_type.";".$res_qty.";".$res_skuname.";".$res_skudescription.";".$res_flexi.";".$res_autorenew.";".$res_billingscope.";".$res_billing.";".$res_expiration.";".$res_archived.";".$res_extendedStatus.";".$res_provisioningstate.";\r\n";

        fwrite($f_reserv_output,$line);
      }


    }

  }

}

fclose($f_reserv_output);
?>
