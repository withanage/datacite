##### OMPDatacite and DA|RA Plugin

###### Einleitung
Dieses Plugin registriert DOIS für Mongraphen, Sammelbände und Kapitel für den DOI-Anbieter [Datacite.org](https://datacite.org).

Darüber hinaus unterstützt es die [da|ra](https://www.da-ra.de/home/) -Registrierung, einen in Deutschland ansässigen  DOI-Registrierungsservice .

Übersetzungen verfügbar in: [Englisch](README.md)
######  Installation
```bash
OMP=/path/to/OMP_INSTALLATION
cd $OMP/plugins/importexport
git clone https://github.com/withanage/datacite
```

######  Setup Datacite
![datacite](www/datacite.png)
* Navigieren zu {OMP_SERVER}/index.php/{MY_PRESS}/management/importexport/plugin/DataciteExportPlugin
* Verwenden Sie da|ra als DOI-Anbieter: Dieses Kontrollkästchen deaktivierern
* Datacite-URL: Verwenden Sie die Test- oder Produktions-URL
* Benutzername: Benutzername
* Passwort: Passwort
* Nur zum Testen: Verwenden Sie das DataCite-Testpräfix für die DOI-Registrierung. Bitte vergessen Sie nicht, diese Option für die Produktion zu entfernen.
* Test registry: (Nur zum Testen), bereitgestellt von datacite
* Test-URL: (nur zum Testen) Produktions-URL zum Überschreiben der XML-Einträge
######  Setup Da|ra
![dara](www/dara.png)
* Verwenden Sie da|ra als DOI-Anbieter: Dieses Kontrollkästchen aktivierern
* Datacite-URL: Verwenden Sie die Test- oder Produktions  API von da|ra
* Benutzername: Benutzername
* Passwort: Passwort
* Nur zum Testen: Verwenden Sie das da|ra-Testpräfix für die DOI-Registrierung. Bitte vergessen Sie nicht, diese Option für die Produktion zu entfernen.
* Test registry: (Nur zum Testen), bereitgestellt von   da|ra
* Test-URL: (nur zum Testen) Produktions-URL zum Überschreiben der XML-Einträge

###### Usage
![usage](www/usage.gif)

######  Credits
*Hauptentwickler und Designer

[https://github.com/withanage](https://github.com/withanage)
