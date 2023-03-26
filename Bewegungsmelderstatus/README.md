# Bewegungsmelderstatus

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Auslöser](#4-auslöser)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
   1. [Status aktualisieren](#51-status-aktualisieren)

### 1. Modulbeschreibung

Dieses Modul zeigt den (Gesamt-)Status (untätig/Bewegung) von Bewegungsmeldern an.

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                      +-------------------------------+
                      | Bewegungsmelderstatus (Modul) |
                      |                               |
Auslöser<-------------+ Status                        |
                      +-------------------------------+
```

### 4. Auslöser

Das Modul Bewegungsmelderstatus reagiert auf verschiedene Auslöser.  

### 5. PHP-Befehlsreferenz

#### 5.1 Status aktualisieren

```
BWMS_UpdateStatus(integer INSTANCE_ID);
```

Konnte der jeweilige Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis:  
**FALSE** für alle Bewegungsmelder sind untätig  
**TRUE** für mindestens einen Bewegungsmelder wurde eine Bewegung erkannt

| Parameter     | Beschreibung   | 
|---------------|----------------|
| `INSTANCE_ID` | ID der Instanz |


Beispiel:
> BWMS_UpdateStatus(12345);

