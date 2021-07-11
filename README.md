# cmdbcloudconnector
Having a constantly update CMDB is something essential for managing every kind of IT infrastructure but sometimes it is too difficult or expensive to buy a CMDB with cloud auto-discover capabilities.

Here cames this project : a series of ready-to-run scripts to extract information from your cloud provider platform and create a series of CSV files with essential informations and relations between objects.
You can then use those CSV extracted files to populate your CMDB, if you have already one, or build your CMDB from scratch, with all open source software using the guides and examples in the WIKI section.

# supported objects
* Azure subscriptions
* Azure virtual machines (and disks)
* Azure app service plan
* Azure webapp
* Azure storage account
* Azure mysql servers and databases
* Azure vnet (not subnet - YET)
* Azure SQL Servers
* Azure reservations (aka reserved instances)
* Azure cosmosdb (minimal support - name, id, location, resourcegroup)

# business applications
Every CMDB should map not only the infrastructure items, but also relations with the "application", to let you have an immediate view of the elements related to each application (every VM, webapp, db, and so on) from a Business Application point of view.
CmdbCloudConnector use two object tags fot this goal:
AppID : a tag to track the ID your object is related to. This could be a name, an id, or everything already in use in your company. You could also map a cloud object to more than one application.
Landscape : a tag to track the landscape your object is related to (Production, pre-production, development, test, quality, problem-determination, ...)

Example 1 : tag an object to application id 9991, production
AppID = "9991"
Landscape = "Production"

Example 2 : tag an object to application id 9991,9992,9993 for quality environment
AppID = "|9991|9992|9993|"
Landscape = "Quality"

The scripts automatically create a CSV with the "business applications" relations of each object with the tags defined.
