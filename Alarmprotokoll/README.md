# Alarmprotokoll

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Externe Aktion](#4-externe-aktion)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
    1. [Meldungen aktualisieren](#51-meldungen-aktualisieren)
    2. [Monatsprotokoll versenden](#52-monatsprotokoll-versenden)
    3. [Archivprotokoll versenden](#53-archivprotokoll-versenden) 

### 1. Modulbeschreibung

Dieses Modul protokolliert Daten und versendet diese als Protokoll per E-Mail.

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1
- Mailer Modul
- SMTP Modul

### 3. Schaubild

```
+-----------------+                   +------------------------+                   +----------------+
| Ereignisse      |                   | Alarmprotokoll (Modul) |------------------>| Mailer (Modul) |
|                 |                   |                        |                   |                |
| Alarmmeldung    +------------------>| Alarmmeldung           |                   | Empfänger 1    |
|                 |                   |                        |                   | Empfänger 2    |
| Zustandsmeldung +------------------>| Zustandsmeldung        |                   | Empfänger 3    |
|                 |                   |                        |                   | Empfänger n    |
| Ereignismeldung +------------------>| Ereignismeldung        |                   |                |
+-----------------+                   +------------------------+                   +-------+--------+
                                                                                           |
                                                                                           |
                                                                                           |
                                                                                           |
                                                                                           |
                                                                                           v
                                                                                   +----------------+
                                                                                   |  SMTP (Modul)  |
                                                                                   +----------------+   
```

### 4. Externe Aktion

Das Modul empfängt über eine externe Aktion die Daten.  
Nachfolgendes Beispiel protokolliert eine Ereignismeldung.

> AP_UpdateMessages(12345, 'Dies ist eine Ereignismeldung', 0);

### 5. PHP-Befehlsreferenz

#### 5.1 Meldungen aktualisieren

```
AP_UpdateMessages(integer INSTANCE_ID, string MESSAGE_TEXT, integer MESSAGE_TYPE);
```

Der Befehl liefert keinen Rückgabewert.

| Parameter       | Beschreibung   | Wert                                                     |
|-----------------|----------------|----------------------------------------------------------|
| `INSTANCE_ID`   | ID der Instanz |                                                          |
| `MESSAGE_TEXT`  | Meldungstext   |                                                          |
| `MESSAGE_TYPE`  | Meldungstyp    | 0 = Ereignismeldung, 1 = Statusmeldung, 2 = Alarmmeldung | 

Beispiel:
> AP_UpdateMessages(12345, 'Dies ist eine Alarmmeldung', 2);

---

#### 5.2 Monatsprotokoll versenden

```
AP_SendMonthlyProtocol(integer INSTANCE_ID, boolean CHECK_DAY, integer PROTOCOL_PERIOD);
```

Der Befehl liefert keinen Rückgabewert.

| Parameter         | Beschreibung              | Wert                                  |
|-------------------|---------------------------|---------------------------------------|
| `INSTANCE_ID`     | ID der Instanz            |                                       |
| `CHECK_DAY`       | Prüft auf den Ereignistag | false = keine Prüfung, true = Prüfung |
| `PROTOCOL_PERIOD` | Protokollzeitraum         | 0 = aktueller Monat, 1 = Vormonat     |

Beispiel:
> AP_SendMonthlyProtocol(12345, 'Hinweis', false, 0);

---

#### 5.3 Archivprotokoll versenden

```
AP_SendArchiveProtocol(integer INSTANCE_ID);
```

Der Befehl liefert keinen Rückgabewert.

| Parameter     | Beschreibung     | 
|---------------|------------------|
| `INSTANCE_ID` | ID der Instanz   |

Beispiel:
> AP_SendArchiveProtocol(12345);

---
