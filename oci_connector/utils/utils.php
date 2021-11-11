<?php
#require_once "./conf/output.php";

function printCommandOutputDebug($cmd, $cmd_output){ 
  global $debug_cmd_output;
//  echo $_debug_cmd_output;
  if ( isset($debug_cmd_output) && $debug_cmd_output ){
          echo "";
	  echo "CMD DEBUG : ".$cmd."\r\n";
	  echo "CMD DEBUG : \r\n";
	  print_r ($cmd_output);
	  echo "\r\n";
  }
}

// le key sono nel formato pippo->pluto->paperino
function getIfExists($json_object, $a_keys){
  //$debug=1;

  $json_temp=$json_object;
  for ($i=0; $i<count($a_keys); $i++){
    if ($debug)	  
      echo "ITER:".$i."\r\n";
    if ( isset($json_temp->{$a_keys[$i]} )){
      if ($debug)
        echo "  ISSET:".$a_keys[$i]."\r\n";

      if ( count($a_keys) == $i+1 ){
	if ($debug)
	  echo "  FOUND : ".$json_temp->{$a_keys[$i]}."\r\n";
        return $json_temp->{$a_keys[$i]};
      }else{
	if ($debug)
	  echo "  DRILLDOWN\r\n";
        $json_temp=$json_temp->{$a_keys[$i]};
      }
    }
  }
  //var_dump($a_keys);
  return "N/A";
}

?>
