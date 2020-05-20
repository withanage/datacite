##### OMPDatacite and DARA Plugin

###### Introduction
This plugin registers DOIS for monographs and chapters  for DOI provider [Datacite.org](https://datacite.org).

Additionally it supports [da|ra](https://www.da-ra.de/home/) registration;  Germany based Datacite DOI registration agency.

######  Installation
```bash
OMP=/path/to/OMP_INSTALLATION
cd $OMP/plugins/importexport
git clone https://github.com/withanage/datacite
```

######  Setup Datacite
![datacite](www/datacite.png)

* Use da|ra as DOI provider: Leave unchecked
* Datacite URL : Use the test or production URL
* Username  : Username
* Password: Password
* Only for testing: Use the DataCite test prefix for DOI registration. Please do not forget to remove this option for the production.
* Test registry:  (Only for testing), procided by datacite or da|ra
* Test URL:  (Only for testing) Production URL for overwriting the XML entries

######  Setup Dara

###### Usage
######  Credits


