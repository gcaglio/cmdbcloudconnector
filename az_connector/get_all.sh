#!/bin/bash

echo "INFO : starting azure connectors"
echo "INFO : get subscriptions"
./bin/get_subscriptions.sh
echo "INFO : get virtual machines"
./bin/get_vm.sh
