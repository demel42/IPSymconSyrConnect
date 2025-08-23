[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Anschluss der Geräte der Firma *Syr* der Modellreihe *Connect* über die [öffentliche API](https://iotsyrpublicapi.z1.web.core.windows.net/#einleitung) im lokalen Netzwerk.

Grundsätzlich werden alle dort aufgeführten Modelle unterstützt; derzeit ist nur das *SafeTech +* ausprogrammiert. Die anderen, in dieser Dokumentation aufgeführten, Modelle, bitte an den Autor wenden.

Es werden alle relevanten Werte ausgelesen und die notwendigen Steuerungen abgebildet; Parametrierung etc. muss über die Hersteller-eigenen Methoden vorgenommen werden.

## 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- ein ensprechendes, im lokalen Netzwerk eingebundenes, Gerät

## 3. Installation

### a. Installation des Moduls

Im [Module Store](https://www.symcon.de/service/dokumentation/komponenten/verwaltungskonsole/module-store/) ist das Modul unter dem Suchbegriff *Syr Connect* zu finden.<br>
Alternativ kann das Modul über [Module Control](https://www.symcon.de/service/dokumentation/modulreferenz/module-control/) unter Angabe der URL `https://github.com/demel42/IPSymconSyrConnect.git` installiert werden.

### b. Einrichtung in IPS

## 4. Funktionsreferenz

alle Funktionen sind über _RequestAction_ der jew. Variablen ansteuerbar

`SyrConnect_SwitchValve(int $InstanzID, bool $val)`<br>
Schaltet das Hauptventil (*true* = offen, *false* = geschlossen)

`SyrConnect_SetActiveProfile(int $InstanzID, int $val)`<br>
Setzt das aktive Profil. Es gibt ein Variablenprofil *SyrConnect.Profiles_\<InstanceID\>* in dem die im Gerät konfigurierten Profile hinterlegt werden müssen.

`SyrConnect_SwitchBuzzer(int $InstanzID, bool $val)`<br>
Aktiviert/Deaktiviert den Alarmsummer

`SyrConnect_ClearCurrentAlarm(int $InstanzID)`<br>
Löscht den aktuellen Alarm

`SyrConnect_ClearCurrentWarning(int $InstanzID)`<br>
Löscht die aktuelle Warnung

`SyrConnect_ClearCurrentNotification(int $InstanzID)`<br>
Löscht die aktuelle Meldung


## 5. Konfiguration

### SyrConnect

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Instanz deaktivieren      | boolean  | false        | Instanz temporär deaktivieren |
|                           |          |              | |
| Host                      | string   |              | Hostname/IP des Gerätes |
|                           |          |              | |
| Gerätetyp                 | int      | 0            | Derzeit nur (0=kein, 1=SafeTech+) |
|                           |          |              | |
| Aktualisierungsintervall  | int      | 60           | Intervall in Sekunden |
|                           |          |              | |

#### Aktionen

| Bezeichnung                | Beschreibung |
| :------------------------- | :----------- |
|                            | |
| Zustand prüfen             | Basisdaten des Geräts zur Überprüfung abrufen und darstellen |
| Aktualisiere Status        | Daten manuell abrufen |
|                            | |

### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Boolean<br>
SyrConnect.Buzzer,
SyrConnect.ValveAction

* Integer<br>
SyrConnect.Alarm,
SyrConnect.Conductivity,
SyrConnect.Flow,
SyrConnect.Hardness,
SyrConnect.MicroleakageTestState,
SyrConnect.Notification,
SyrConnect.Profiles_\<InstanceID\>
SyrConnect.Seconds,
SyrConnect.ValveState,
SyrConnect.Warning

* Float<br>
SyrConnect.Pressure,
SyrConnect.Temperature,
SyrConnect.Voltage,
SyrConnect.Volume

## 6. Anhang

### GUIDs
- Modul: `{4A7B6863-CC71-D595-BC77-C0B14043994B}`
- Instanzen:
  - SyrConnect: `{69C41FD6-14D2-6296-88BB-D0C0300F8FEB}`
- Nachrichten:

### Quellen
- API-Dokumentation: https://iotsyrpublicapi.z1.web.core.windows.net

## 7. Versions-Historie

- 1.0 @ 23.08.2025 10:35
  - Initiale Version
