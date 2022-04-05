<?php
include "./conf/output.php";
include "./conf/tags.php";
include "./utils/utils.php";

// get all projects

$prjs_output=null;
$prjs_retval=null;
$prjs_command="gcloud projects list --format=json 2>/dev/null";

	
exec($prjs_command, $prjs_output, $prjs_retval);
printCommandOutputDebug($prjs_command,$prjs_output);

$prjs_json_obj=json_decode(join($prjs_output),false);

// output file for projects
$out_prjs_filepath=$output_path."/".$out_prjs_filename;
$f_prjs_output = fopen($out_prjs_filepath, "w") or die("Unable to open file : ".$out_prjs_filepath);
fwrite($f_prjs_output,"Code;Id;Number;Name;State\r\n");


// cycle on all projects
for ($s=0; $s<count($prjs_json_obj); $s++){   
    $prj_id=$prjs_json_obj[$s]->{"projectId"};	
    $prj_name=$prjs_json_obj[$s]->{"name"};

    $hash_prjid=md5(strtolower($prj_id));	 

    $prj_number=$prjs_json_obj[$s]->{"projectNumber"};
    $prj_lifecycleState=$prjs_json_obj[$s]->{"lifecycleState"};

    echo "INFO : working on project : ".$prj_id."\r\n";

    $line=$hash_prjid.";".$prj_id.";".$prj_number.";".$prj_name.";".$prj_lifecycleState."\r\n";
    fwrite($f_prjs_output, $line);
}	

fclose($f_prjs_output);
?>
