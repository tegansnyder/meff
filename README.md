The purpose of this utility is to list all the files responsible for a Magento extension and their locations. Think of it as an experiment in building a automatic modman file generator. Key word "experiment" :)

#### Usage:
Simple clone this repo and run the meff.php file via the command line passing it a extension name and full path to your Magento root directory.
```bash
php meff.php Extension_Name MagentoDir

# or if you are in the current working directory
php meff.php Extension_Name
```
You can also install this via modman:
```bash
modman clone https://github.com/tegansnyder/meff.git
```

##### Example:
[Comparison >](https://github.com/SchumacherFM/Magento-OpCache/blob/master/modman)
```bash
➜ php meff.php SchumacherFM_OpCachePanel /var/www/site
FILES IDENTIFIED FOR: SchumacherFM_OpCachePanel
/var/www/site/app/code/community/SchumacherFM/OpCachePanel
/var/www/site/app/etc/modules/SchumacherFM_OpCachePanel.xml
/var/www/site/app/design/adminhtml/default/default/layout/opcachepanel.xml
/var/www/site/app/locale/de_DE/SchumacherFM_OpCachePanel.csv
/var/www/site/app/locale/en_US/SchumacherFM_OpCachePanel.csv
/var/www/site/app/design/adminhtml/default/default/template/opcachepanel/unloaded.phtml
/var/www/site/app/design/adminhtml/default/default/template/opcachepanel/graph.phtml
```

#### Debug Mode:
This extension has some built in debug modes you can enable to see what it is doing behind the scenes. In the `meff.php` file there are two constants you can use to control the debug output.
```bash
    const DEBUG_MODE = true;
    /*
    log levels:
        0 = all
        1 = normal
        2 = file list only
    */
    const DEBUG_LEVEL = 2;
```

#### Caveats:
I haven't tested this on all possible senarios. I appreciate the communities support in testing it with extensions. Magento allows you to construct extensions that can pull files in from a wide variety of sources. I attempt parse the source code looking for mentions of this files and then attempt to determine their existances passed on a few testable assumptions. I'm still working on a few things:
 * Magento allows you to define a helper function to assist in returning a filename using the addItem method. Since this extension currently doesn't instantiate the Magento framework I haven't added this feature.
 * I attempt to pickup any files the extension places in the /lib folder by parsing the source of the php files in the extension and looking for new class declarations. In my tests it is working, but if an issue is found please submit a PR.
 * The code is a bit messy and documentation is limited in some places. I appreciate PR's for refactoring.

--------------

#### Contribution

To contribute:

This repo uses the [git flow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow) branching model. Pull requests should be issued to the **develop branch**.

--------------

#### The MIT License

Copyright 2015 - Tegan Snyder, All Rights Reserved

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

