<?php
// YOU HAVE TO  modify this path to adapt to your desired output folder.
$output_path="/home/demouser/cmdbcloudconnector/gcp_connector/output";

// true if you want every command and output to be printed for debug
$debug_cmd_output=true;

// output file anag
$out_vm_filename="vm.csv";
$out_gke_filename="gke_instances.csv";
$out_gkec_filename="gke_cluster.csv";
$out_bti_filename="bti.csv";
$out_disks_filename="disks.csv";
$out_prjs_filename="projects.csv";


// output file relations
$out_rel_disks_vm="rel_disks-vm.csv";
$out_rel_prjs_disks="rel_prjs-disk.csv";
$out_rel_prjs_vm="rel_prjs-vm.csv";
$out_rel_prjs_gke="rel_prjs-gkeinstances.csv";
$out_rel_prjs_gkec="rel_prjs-gkeclusters.csv";
$out_rel_prjs_bti="rel_prjs-bti.csv";
#$out_rel_busapplandscape_vm="rel_bal-vm.csv";
#$out_rel_busapp_busapplandscape="rel_ba-bal.csv";

?>
