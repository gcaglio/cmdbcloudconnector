<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all subscriptions
// for each enabled subscriptions get redis cache

$subs_output=null;
$subs_retval=null;
$subs_command="az account list --all --refresh 2>/dev/null";

exec($subs_command, $subs_output, $subs_retval);
printCommandOutputDebug($subs_command,$subs_output);

$subs_json_obj=json_decode(join($subs_output),false);

// output file for redis
$out_redis_filepath=$output_path."/".$out_redis_filename;
$f_redis_output = fopen($out_redis_filepath, "w") or die("Unable to open file : ".$out_redis_filepath);
fwrite($f_redis_output,"Code;Id;Name;Location;Hostname;IsMaster;IsPrimary;PublicNetworkAccess;MaxClient;MaxFragmentationMemoryReserved;MaxMemoryDelta;MaxMemoryReserved;RedisVersion;ShardCount;SkuCapacity;SkuFamily;SkuName;ResourceGroup;Type\r\n");

//output file for SUBS-REDIS
$out_rel_subs_redis_filepath=$output_path."/".$out_rel_subs_redis;
$f_rel_subs_redis_output = fopen($out_rel_subs_redis_filepath, "w") or die("Unable to open file : ".$out_rel_subs_redis_filepath);
fwrite($f_rel_subs_redis_output,"hash_SubsId;hash_RedisId\r\n");


//output file for BusinessAppLandscape-Redis
$out_rel_bal_redis_filepath=$output_path."/".$out_rel_busapplandscape_redis;
$f_rel_bal_redis_output = fopen($out_rel_bal_redis_filepath, "w") or die("Unable to open file : ".$out_rel_bal_redis_filepath);
fwrite($f_rel_bal_redis_output,"code_baLandscape;hash_RedisId\r\n");


// cycle on all subscriptions
for ($s=0; $s<count($subs_json_obj); $s++){
  if ( $subs_json_obj[$s]->{"state"} === "Enabled" ){ 
    $subs_id=$subs_json_obj[$s]->{"id"};	
    $hash_subsid=md5(strtolower($subs_id));	 

    echo "INFO : working on subscription : ".$subs_id."\r\n";

    $redis_output=null;
    $redis_retval=null;

    $redis_command="az redis list --subscription ".$subs_id;  

    exec($redis_command, $redis_output, $redis_retval);
    printCommandOutputDebug($redis_command,$redis_output);
    $redis_json_obj=json_decode(join($redis_output),false);
    //cycle on all Redis
    echo "INFO : found ".count($redis_json_obj)." Redis Cache(s)\r\n";
    for ($v=0; $v<count($redis_json_obj); $v++){
      #var_dump($vm_json_obj[$v]);
      $redis_id=$redis_json_obj[$v]->{"id"};
      $hash_redisid=md5(strtolower($redis_id));                 // this will be the CODE 32-byte length
      $redis_name=$redis_json_obj[$v]->{"name"};
      $redis_location=$redis_json_obj[$v]->{"location"};

      $redis_ismaster=getIfExists($redis_json_obj[$v]->{"instances"}[0],["isMaster"]);
      echo "MST".$redis_name.";".$redis_ismaster."#\r\n";

      $redis_isprimary=getIfExists($redis_json_obj[$v]->{"instances"}[0],["isPrimary"]);
      echo "PRI".$redis_name.";".$redis_isprimary."#\r\n";

      $redis_publicnetworkaccess=getIfExists($redis_json_obj[$v],["publicNetworkAccess"]);
      $redis_maxclient=getIfExists($redis_json_obj[$v],["redisConfiguration","maxclients"]);
      $redis_maxfragmemoryreserved=getIfExists($redis_json_obj[$v],["redisConfiguration","maxfragmentationmemory-reserved"]);
      $redis_maxmemorydelta=getIfExists($redis_json_obj[$v],["redisConfiguration","maxmemory-delta"]);
      $redis_maxmemoryreserved=getIfExists($redis_json_obj[$v],["redisConfiguration","maxmemory-reserved"]);

      $redis_version=$redis_json_obj[$v]->{"redisVersion"};
      $redis_replicapermaster=getIfExists($redis_json_obj[$v],["replicasPerMaster"]);
      $redis_replicaperprimary=getIfExists($redis_json_obj[$v],["replicasPerPrimary"]);
      $redis_resourcegroup=$redis_json_obj[$v]->{"resourceGroup"};
      $redis_shardcount=$redis_json_obj[$v]->{"shardCount"};
      $redis_skucapacity=$redis_json_obj[$v]->{"sku"}->{"capacity"};
      $redis_skufamily=$redis_json_obj[$v]->{"sku"}->{"family"};
      $redis_skuname=$redis_json_obj[$v]->{"sku"}->{"name"};


      $redis_type=$redis_json_obj[$v]->{"type"};
      $redis_zones="";
      if ( isset($redis_json_obj[$v]->{"zones"}) ){
        for ($z=0; $z<count($redis_json_obj[$v]->{"zones"}); $z++){
          $redis_zones=$redis_json_obj[$v]->{"zones"}[$z]." ";  
	}
      }

      // write line in Redis(es) file
      $line=$hash_redisid.";".$redis_id.";".$redis_name.";".$redis_location.";".$redis_hostname.";".$redis_ismaster.";".$redis_isprimary.";".$redis_publicnetworkaccess.";".$redis_maxclient.";".$redis_maxfragmemoryreserved.";".$redis_maxmemorydelta.";".$redis_maxmemoryreserved.";".$redis_version.";".$redis_shardcount.";".$redis_skucapacity.";".$redis_skufamily.";".$redis_skuname.";".$redis_resourcegroup.";".$redis_type."\r\n";
      fwrite($f_redis_output, $line);

      // write line in rel-subs-redis file
      $rel_line=$hash_subsid.";".$hash_redisid."\r\n";
      fwrite($f_rel_subs_redis_output,$rel_line);

      // get landscape from tag
      $landscape="";
      if (isset($redis_json_obj[$v]->{"tags"}->{$tag_landscape} )){
        $landscape=$redis_json_obj[$v]->{"tags"}->{$tag_landscape};
      }

      // split businessApp, add lines in relation file
      if (isset($redis_json_obj[$v]->{"tags"}->{$tag_appid})){
        $appids=explode($tag_appid_separator,$redis_json_obj[$v]->{"tags"}->{$tag_appid});
       	for ($t=0;$t<count($appids); $t++){
          if (strlen($appids[$t])>0){
	    $temp_landscape=$landscape;		  
            if (strlen($landscape)>0)
	      $temp_landscape="_".$landscape;

	    $rel_line=$appids[$t].$temp_landscape.";".$hash_redisid."\r\n";
	    fwrite($f_rel_bal_redis_output,$rel_line);
	  }
        }
      }


    }

  }
	
}	

fclose($f_redis_output);
fclose($f_rel_subs_redis_output);
fclose($f_rel_bal_redis_output);

?>
