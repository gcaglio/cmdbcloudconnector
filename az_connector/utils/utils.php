<?php
#require_once "./conf/output.php";

function printCommandOutputDebug($cmd, $cmd_output){ 
  global $debug_cmd_output;
//  echo $_debug_cmd_output;
  if ( isset($debug_cmd_output) && $debug_cmd_output ){
          echo "";
	  echo "CMD DEBUG : ".$cmd;
	  echo "CMD DEBUG : ";
	  print_r ($cmd_output);
	  echo "";
  }
}

?>
