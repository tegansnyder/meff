The purpose of this utility is to list all the files responsible for a Magento extension and their locations. Think of it as an experiment in building a automatic modman file generator. Key word "experiment" :)

####Usage:
Simple clone this repo and run the meff.php file via the command line passing it a extension name and full path to your Magento root directory.
```bash
php meff.php Extension_Name MagentoDir
```

#####Example:
```bash
php meff.php ProxiBlue_NewRelic /var/www/magento
/var/www/magento/app/design/adminhtml/default/default/template/dashboard
/var/www/magento/app/design/adminhtml/default/default/template/newrelic
/var/www/magento/app/etc/modules/ProxiBlue_NewRelic.xml
/var/www/magento/app/code/community/ProxiBlue/NewRelic
/var/www/magento/app/design/adminhtml/default/default/layout/newrelic.xml
```

####Caveats:
There is lots to refactor and places where I'm not doing stuff correctly. Submit a PR if you want to help. There are currently some files I'm not able to pick up on (i'm hoping to in further releases):
 * not working with addItem using a helper function to chose the file filename
 * Any other non-standard files that the extension adds.
 * the code is a bit messy and documentation is limited