#!/bin/bash

basedir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

cd $basedir
echo "INFO : starting Google Cloud connectors $basedir"
echo "INFO : get projects"
$basedir/bin/get_projects.sh
echo "INFO : get virtual machines (compute instances)"
$basedir/bin/get_vm.sh
echo "INFO : get disks (compute instances)"
$basedir/bin/get_disks.sh
echo "INFO : get gke clusters"
$basedir/bin/get_gke_clusters.sh

#echo "INFO : get app service plans"
#$basedir/bin/get_appsvcplan.sh
#echo "INFO : get webapps"
#$basedir/bin/get_webapp.sh
#echo "INFO : get storage accounts"
#$basedir/bin/get_stgaccount.sh
#echo "INFO : get mysql servers and databases"
#$basedir/bin/get_mysql.sh        
#echo "INFO : get vnet"
#$basedir/bin/get_vnet.sh
#echo "INFO : get sql servers and databases"
#$basedir/bin/get_sqlserver.sh
#echo "INFO : get reservations"
#$basedir/bin/get_reservations.sh
#echo "INFO : get cosmosdb"
#$basedir/bin/get_cosmosdb.sh
#echo "INFO : get iothub"
#$basedir/bin/get_iothub.sh
#echo "INFO : get svcbus"
#$basedir/bin/get_svcbus.sh

echo "END"
