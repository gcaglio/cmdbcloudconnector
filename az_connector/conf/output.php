<?php
// YOU HAVE TO  modify this path to adapt to your desired output folder.
$output_path="/home/demouser/cmdbcloudconnector/az_connector/output";

// true if you want every command and output to be printed for debug
$debug_cmd_output=true;

// output file anag
$out_vm_filename="vm.csv";
$out_redis_filename="redis.csv";
$out_disks_filename="disks.csv";
$out_subs_filename="subs.csv";
$out_webapp_filename="webapp.csv";
$out_stgaccount_filename="stgaccount.csv";
$out_appsvcplan_filename="appsvc-plan.csv";
$out_mysqlsrv_filename="mysql-srv.csv";
$out_sqlsrv_filename="sqlserver.csv";
$out_mysqldb_filename="mysql-db.csv";
$out_sqlsrvdb_filename="sqlserver-db.csv";
$out_vnet_filename="vnets.csv";
$out_cosmos_filename="cosmosdb.csv";
$out_iothub_filename="iothub.csv";
$out_svcbus_filename="servicebus.csv";
$out_reserv_filename="reservations.csv";
$out_privip_filename="privateips.csv";
$out_anfaccount_filename="anf_account.csv";
$out_anfpool_filename="anf_pool.csv";
$out_anfvol_filename="anf_vol.csv";


// output file relations
$out_rel_disks_vm="rel_disks-vm.csv";
$out_rel_subs_disk="rel_subs-disk.csv";
$out_rel_subs_vm="rel_subs-vm.csv";
$out_rel_subs_redis="rel_subs-redis.csv";
$out_rel_busapplandscape_vm="rel_bal-vm.csv";
$out_rel_busapplandscape_redis="rel_bal-redis.csv";
$out_rel_busapp_busapplandscape="rel_ba-bal.csv";
$out_rel_busapplandscape_vm="rel_bal-vm.csv";
$out_rel_busapplandscape_mysqlsrv="rel_bal-mysqlsrv.csv";

$out_rel_subs_appsvcplan="rel_subs-appsvcplan.csv";
$out_rel_appsvcplan_webapp="rel_appsvcplan-webapp.csv";
$out_rel_busapplandscape_appsvcplan="rel_bal-appsvcplan.csv";
$out_rel_busapplandscape_webapp="rel_bal-webapp.csv";
$out_rel_busapplandscape_stgaccount="rel_bal-stgaccount.csv";
$out_rel_busapplandscape_mysqldb="rel_bal-mysqldb.csv";
$out_rel_mysqlsrv_mysqldb="rel_mysqlsrv-mysqldb.csv";

$out_rel_subs_mysqlsrv="rel_subs-mysqlsrv.csv";

$out_rel_busapplandscape_vnet="rel_bal-vnet.csv";
$out_rel_busapplandscape_cosmos="rel_bal-cosmosdb.csv";
$out_rel_busapplandscape_iothub="rel_bal-iothub.csv";
$out_rel_busapplandscape_svcbus="rel_bal-svcbus.csv";
$out_rel_busapplandscape_sqlsrv="rel_bal-sqlsrv.csv";
$out_rel_busapplandscape_sqldb="rel_bal-sqlsrvdb.csv";
$out_rel_sqlsrv_sqldb="rel_sqlsrv-srvsqldb.csv";

$out_rel_subs_sqlsrv="rel_subs-sqlsrv.csv";
$out_rel_subs_cosmos="rel_subs-cosmosdb.csv";
$out_rel_subs_iothub="rel_subs-iothub.csv";
$out_rel_subs_svcbus="rel_subs-svcbus.csv";
$out_rel_privip_vnet="rel_privip-vnet.csv";

$out_rel_subs_anfaccount="rel_subs-anfaccount.csv";
$out_rel_anfpool_anfaccount="rel_anfpool-anfaccount.csv";
$out_rel_anfpool_anfvol="rel_anfpool-anfvolume.csv";
$out_rel_busapplandscape_anfvol="rel_bal-anfvolume.csv";
?>
