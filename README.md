================================
=== OMP DataCite Export Plugin
=== Version: 1.0
=== Author: Dulip Withanage
=== Based on OJS datacite plugin
================================

About
-----
This plugin enables the export of issue, article and galley metadata in DataCite format and
the registration of DOIs with DataCite.

License
-------
This plugin is licensed under the GNU General Public License v2. See the file COPYING for the
complete terms of this license.

System Requirements
-------------------
OMP 3.1.2 +

Note
---------
In order to register DOIs with DataCite from within OMP you will have to enter your username and password.
If you do not enter or have your own username and password you'll still be able to export into the
DataCite XML format but you cannot register your DOIs from within OMP.
Please note, that the passowrd will be saved as plain text, i.e. not encrypted, due to DataCite registration service requirements.

```sql
INSERT INTO `omp3_2_0`.`filter_groups` ( `symbolic`, `display_name`, `description`, `input_type`, `output_type`) VALUES ('monograph=>datacite-xml', 'plugins.importexport.datacite.displayName', 'plugins.importexport.datacite.description', 'class::classes.submission.Submission[]', 'xml::schema(http://schema.datacite.org/meta/kernel-4/metadata.xsd)');
```
