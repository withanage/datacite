``##### OMPDatacite and DARA Plugin

###### Einleitung
Dieses Plugin registriert DOIS für Mongraphen, Sammelbände und Kapitel für den DOI-Anbieter [Datacite.org](https://datacite.org).

Darüber hinaus unterstützt es die [da|ra](https://www.da-ra.de/home/) -Registrierung, einen in Deutschland ansässigen  DOI-Registrierungsservice .

######  Installation
```bash
OMP=/path/to/OMP_INSTALLATION
cd $OMP/plugins/importexport
git clone https://github.com/withanage/datacite
```

######  Setup Datacite
![datacite](www/datacite.png)
* Verwenden Sie da|ra als DOI-Anbieter: Dieses Kontrollkästchen deaktivierern
* Datacite-URL: Verwenden Sie die Test- oder Produktions-URL
* Benutzername: Benutzername
* Passwort: Passwort
* Nur zum Testen: Verwenden Sie das DataCite-Testpräfix für die DOI-Registrierung. Bitte vergessen Sie nicht, diese Option für die Produktion zu entfernen.
* Test registry: (Nur zum Testen), bereitgestellt von datacite oder da|ra
* Test-URL: (nur zum Testen) Produktions-URL zum Überschreiben der XML-Einträge
######  Setup Da|ra


#######  Production server
#######  Test Server
###### Usage
######  Credits
