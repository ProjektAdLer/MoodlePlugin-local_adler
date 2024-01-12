# AdLer Moodle Plugin

[![Coverage Status](https://coveralls.io/repos/github/ProjektAdLer/MoodlePluginLocal/badge.svg?branch=main)](https://coveralls.io/github/ProjektAdLer/MoodlePluginLocal?branch=main)


## Dependencies
> [!CAUTION]
> Now removed all dependency checks for all plugins as I'm pissed of about moodle saying: no you cannot update, because
> the updated version of the dependency that IS ALREADY PRESENT IN THE CORRESPONDING DIRECTORY is "Unavailable".

| Plugin             | Version |
|--------------------|---------|
| availability_adler | ~3.0.0  |



## MBZ api endpunkt
Damit der mbz api endpunkt auch mit größeren Dateien funktioniert sind folgende Änderungen an der php.ini notwendig:
```
- `post_max_size` auf mindestens 2048M setzen
- `upload_max_filesize` auf 2048M setzen
- `max_input_time` auf 600 setzen
- `memory_limit` auf mindestens 256M setzen
- `max_execution_time` auf mindestens 60 setzen
- `output_buffering` auf 8192 setzen
```
# todo: some variable might not be required anymore after upload_course rework


## Kompabilität
Die minimal notwendige Moodle Version ist auf 4.1.0 gesetzt, daher wird die Installation auf älteren Versionen nicht funktionieren.
Prinzipiell sollte dieses Plugin auch auf älteren Versionen funktionieren, dies wird aber nicht getestet und spätestens bei der 
Nutzung weiterer AdLer Plugins wird es zu Problemen kommen, da diese Features nutzen, die erst in neueren Moodle Versionen verfügbar sind.

Folgende Versionen werden unterstützt (mit mariadb und postresql getestet):

| Moodle Branch           | PHP Version |
|-------------------------|-------------|
| MOODLE_401_STABLE (LTS) | 8.1         |
| MOODLE_402_STABLE       | 8.1         |
| MOODLE_402_STABLE       | 8.2         |
| MOODLE_403_STABLE       | 8.1         |
| MOODLE_403_STABLE       | 8.2         |


## Deinstallation der Plugins
### Halbautomatische Deinstallation
Es gibt ein dev_utils script, welches dieses Plugin deinstalliert `php local/adler/dev_utils/uninstall_plugin.php`.
Möglicherweise wird dieses Script in Zukunft zu einem PHP CLI script ausgebaut.

### Manuelle Deinstallation und Erklärung des Problems
Die Plugins sind gegenseitig voneinander abhängig. Diese sind ausschließlich dafür gedacht in Kombination verwendet zu werden, 
für sich alleine können diese nicht eingesetzt werden. Der Uninstaller von moodle kann Plugins mit gegenseitigen Abhängigkeiten
nicht deinstallieren. Dazu gibt es eine "Won't fix" Issue im moodle issue tracker: [MDL-55624](https://tracker.moodle.org/browse/MDL-56624).
Die Deinstallation der Plugins erfordert deshalb manuelles Eingreifen.
1. In der Datei `local/adler/version.php` das Feld `$plugin->dependencies` komplett löschen.
2. Evtl. ist ein Erhöhen der Versionsnummer und ein upgrade der Moodle Installation notwendig.
3. Die Plugins können nun deinstalliert werden.

Ein alternativer Ansatz dazu könnte wie folgt aussehen: Das Plugin `availability_adler` kann im Grunde eigenständig installiert werden,
ist dann aber ohne Funktion, da es ohne das Plugin `local_adler` nicht funktionieren kann (alle Adler-Conditions werden deshalb als 
erfüllt behandelt). Die Abhängigkeit des Plugins `availability_adler` von `local_adler` könnte somit entfernt werden. So könnten 
die Plugins erwartungsgemäß deinstalliert werden. Dieser Ansatz hätte aber die folgenden gravierenden Nachteile:
- Dem Moodle Plugin Konzept folgend dürfte `availability_adler` nicht auf Funktionen von `local_adler` zugreifen, da keine Abhängigkeit
  besteht. Dies würde aber bedeuten, dass die Adler Raumlogiken nicht funktionieren können.
- Wird `local_adler` deinstalliert verbleibt `availability_adler` installiert und muss danach manuell entfernt werden.
- Wird `availability_adler` installiert wird `local_adler` nicht automatisch installiert.


## Setup

Setup funktioniert exakt wie bei allen anderen Plugins auch (nach dem manuellen Installationsvorgang, da unser Plugin nicht im Moodle Store ist).

1. Dieses Plugin benötigt das Plugin `availability_adler` als Abhängigkeit. Beide müssen zeitgleich installiert werden (= vor dem upgrade in die Moodle-Installation entpackt sein). Installation siehe `availability_adler`
2. Plugin in moodle in den Ordner `local` entpacken (bspw moodle/local/adler/lib.php muss es geben)
3. Moodle upgrade ausführen

Damit ist die Installation abgeschlossen. Als Nächstes kann ein mbz mit den Plugin-Feldern wiederhergestellt werden.


### Kurs mit dummy Daten seeden
Für Testzwecke können bestehende normale Kurse mit dummy Daten gefüllt werden.
Im Ordner `dev_utils` liegt dazu das Skript `seed.php`.

Beispiel: \
Für den Kurs mit der ID 142 werden Daten für folgende Elemente geseedet:
- Kurs: Markierung als Adler Kurs
- Section (Räume): availability conditions & required points to complete
- course modules (Lernelemente): AdLer Scoring
Befehl: `php local/adler/dev_utils/seed.php --course-id=142 -c -m -s`

Nun kann dieser Kurs zum Testen genutzt werden.


## Dev Setup / Tests
Dieses Plugin nutzt Mockery für Tests. 
Die composer.json von Moodle enthält Mockery nicht, daher enthält das Plugin eine eigene composer.json.
Diese ist (derzeit) nur für die Entwicklung relevant, sie enthält keine Dependencies die für die normale Nutzung notwendig sind.
Um die dev-dependencies zu installieren muss `composer install` im Plugin-Ordner ausgeführt werden.

### Unit Test Setup
- Den richtigen Interpreter wählen (im Falle von der Nutzung von WSL muss bspw unbedingt der Interpreter innerhalb der WSL Installation gewählt werden)
- moodle composer.json installieren (`cd <path-to-moodle>; composer install`)
- plugin composer.json installieren (für jedes Plugin dessen tests ausgeführt werden sollen) (`cd <path-to-plugin>; composer install`)
- Moodle hat eine hardcoded locale dependency bei Tests für `en_AU.UTF-8` ([siehe docs](https://docs.moodle.org/dev/Common_unit_test_problems#core_phpunit_advanced_testcase::test_locale_reset)). Diese muss generiert werden: Ubuntu: `sudo locale-gen en_AU.UTF-8`
- phpunit.xml aus dem Projekt auswählen (geschieht über den `--configuration` switch von phpunit)

Hinweise:
- Um Tests lokal nicht in `@RunTestsInSeparateProcesses` ausführen zu müssen in der Datei `moodle/lib/setuplib.php` in der Funktion `require_phpunit_isolation()` vor der Exception `return;` einfügen. \
**Achtung**: Dies kann potentiell unerwartete Nebeneffekte haben! Tests werden nicht umsonst normalerweise in isolierten Prozessen ausgeführt.


## Verschiedenes

### Context ID für xapi events

xapi Events nutzen die Context-ID, um die Kurs-ID zu referenzieren.
Diese sind aktuell über keine API des Plugins verfügbar, da sie für das moodle-interne Rechtemanagement gedacht sind.
Um für Testzwecke die context-id eines Lernelements zu erhalten, kann wie folgt vorgegangen werden:

1. cmid des Lernelements herausfinden: h5p Element im Browser öffnen, in der URL steht die cmid als Parameter `id=123` (bspw `http://localhost/mod/h5pactivity/view.php?id=2`)
2. in der DB folgende query ausführen: `SELECT id FROM mdl_context where contextlevel = 70 and instanceid = 2;`  (`instanceid` ist die id aus step 1)


### Löschen eines Kurses / von Lernelementen

Beim Löschen eines Kurses oder Lernelements werden die Daten aus der Datenbank gelöscht. Dabei gibt es aber zwei Dinge zu beachten:

1. Moodle hat einen Trashbin der standardmäßig aktiv ist. Dann werden die Daten erst Zeitverzögert gelöscht. Ob dies wirklich funktioniert wurde bisher nicht getestet, sollte aber
   funktionieren.
2. Wird ein Kurs gelöscht gibt es keine mir bekannte Möglichkeit zu erfahren, welche Kursbestandteile dabei entfernt wurden.
   Daher wird nach Löschen eines Kurses für alle Adler-Score Einträge verglichen, ob das dazugehörige cm noch existiert. Dies könnte bei sehr großen Moodle Instanzen zu Performance
   Problemen führen.
   Die folgende Tabelle zeigt einige Testergebnisse für eine verschiedene Anzahl von Lernelementen in einer Moodle Installation. 
   Ausgeführt wurde der Test auf einem Github Actions Runner.

| Anzahl verbleibende Lernelemente mit Adler-Scores | Anzahl der Lernelemente mit Adler-Scores des gelöschten Kurses | Zeit  |
|---------------------------------------------------|----------------------------------------------------------------|-------|
| 100                                               | 10                                                             | 0,01s |
| 100                                               | 100                                                            | 0,07s |
| 1k                                                | 10                                                             | 0,05s |
| 1k                                                | 100                                                            | 0,12s |
| 10k                                               | 10                                                             | 2,4s  |
| 10k                                               | 100                                                            | 2,5s  |
| 100k                                              | 100                                                            | 186s  |

Ein alternativer Ansatz wäre eine redundante Datenhaltung der Kurs-ID. Dies würde die Performanceprobleme umgehen.
