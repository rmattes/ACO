ACO
============

ACO Plugin, entwickelt durch Studenten des StuPro IZIS (betreut durch das FMI Abteilung Algorithmik) an der Uni Stuttgart. Das Plugin unterliegt der GNU/GPL. 

**HINWEIS**: Dieses Plugin wird open source der ILIAS Community zur Verfügung gestellt. Bei Fragen senden Sie uns eine E-Mail.

# Beschreibung
Das ACO Plugin ist ein UIHook Plugin für die E-Learning Plattform ILIAS. Es ermöglicht das einfache erstellen und verwalten einer Gruppenstruktur mit Hilfe zusätzlicher Tabs (Gruppen verwalten, Gruppen erstellen, Mitglieder verschieben), sowie ein vereinfachtes Eintragen der Punkte für Abgaben, in dem sich Abgaben nach Gruppen filtern lassen.

Zudem lassen sich Excercises und Tests ebenfalls mit einem neuen Tab einfacher verlinken. Die vorgesehen Kursstruktur enthält dabei einen Admin Folder, über den sich die einzelnen Inhalte für die Gruppen verlinken lassen. 

# Documentation


## Installation
Beginnend im ILIAS-root-Verzeichnis:
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://github.com/Ilias-fmi/ACO.git
```
Als ILIAS Administrator, installieren und aktivieren Sie das Plugin unter "Administration->Plugins".

## Functionality

### Gruppen verwalten/Manage groups tab 
Zeigt innerhalb von Kursen drei weitere Subtabs an / shows inside of courses three subtabs

#### Gruppen erstellen/Create groups tab
Dieser Subtab ermöglicht beliebig viele Gruppen auf einmal anzulegen, mit den Parametern max. Mitglieder, Gruppenname, Beitrittstyp (mit oder ohne Passwort), Zeitrahmen (zeitlich begrenzter Beitritt) und Ordnerstruktur in den erstellten Gruppen und des Kurses. 

#### Kurs bearbeiten/Edit course tab
Dieser Subtab ermöglicht sich alle Gruppen im Kurs anzeigen zu lassen und deren Parameter Gruppenname, Beschreibung (Raum/Uhrzeit), Tutor (Gruppenadmin), max. Mitglieder und zeitlich begrenzter Beitritt.  

#### Mitglieder verschieben/Move a group member tab
Dieser Subtab ermöglicht es Gruppenmitglieder innerhalb des Kurses in eine andere Gruppe zu verschieben. 

### Verlinkung/Link tab
Dieser Tab existiert innerhalb von Übungen und Tests / This tab exists inside of excercises and tests

Dabei lassen sich über diesen Übungen oder Tests in die einzelnen Gruppen verlinken, wahlweise von einem Admin Ordner in einen Ordner in den Gruppen. 

### Gruppenfilter/groupfilter tab
Dieser Tab existiert innerhalb von Übungen / This tab exists inside of excercises

Hiermit lassen sich in Übungen Abgaben nach Gruppen gefiltert anzeigen und herunterladen.  

**Eine genauere Beschreibung der Funktionen bzw. Dokumentation und ein Manual finden sie hier**
https://github.com/Ilias-fmi/ACO/blob/master/doc/Dokumentation.pdf


### Contact

Manuel Mergel  merglml@studi.informatik.uni-stuttgart.de 
    
Kai Durst durstrl@studi.informatik.uni-stuttgart.de
    
Philipp Gruber gruberpp@studi.informatik.uni-stuttgart.de




