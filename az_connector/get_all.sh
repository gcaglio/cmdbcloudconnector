#!/bin/bash

basedir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

cd $basedir
echo "INFO : starting azure connectors $basedir"
echo "INFO : get subscriptions"
$basedir/bin/get_subscriptions.sh
echo "INFO : get virtual machines"
$basedir/bin/get_vm.sh
echo "INFO : get app service plans"
$basedir/bin/get_appsvcplan.sh
echo "INFO : get webapps"
$basedir/bin/get_webapp.sh
echo "INFO : get storage accounts"
$basedir/bin/get_stgaccount.sh

echo "END"
