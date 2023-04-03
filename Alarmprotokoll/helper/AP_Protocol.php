<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Protocol.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpExpressionResultUnusedInspection */

declare(strict_types=1);

trait AP_Protocol
{
    /**
     * Sends the monthly protocol via mail.
     *
     * @param bool $CheckDay
     * false =  don't check day
     * true =   check day
     *
     * @param int $ProtocolPeriod
     * 0 =  actual month
     * 1 =  last month
     *
     * @return void
     * @throws Exception
     */
    public function SendMonthlyProtocol(bool $CheckDay, int $ProtocolPeriod): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Tagesprüfung: ' . json_encode($CheckDay), 0);
        $this->SendDebug(__FUNCTION__, 'Protokollzeitraum: ' . $ProtocolPeriod, 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseMonthlyProtocol')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Monatsprotokoll ist nicht aktiviert!', 0);
            return;
        }
        $monthlySMTP = $this->ReadPropertyInteger('MonthlySMTP');
        if ($monthlySMTP > 1 && @IPS_ObjectExists($monthlySMTP)) { //0 = main category, 1 = none
            //Check if it is the correct day of the month
            $day = date('j');
            if ($day == $this->ReadPropertyInteger('MonthlyProtocolDay') || !$CheckDay) {
                //Prepare data
                $archive = $this->ReadPropertyInteger('Archive');
                if ($archive != 0) {
                    //This month
                    $startTime = strtotime('first day of this month midnight');
                    $endTime = strtotime('first day of next month midnight') - 1;
                    //Last month
                    if ($ProtocolPeriod == 1) {
                        $startTime = strtotime('first day of previous month midnight');
                        $endTime = strtotime('first day of this month midnight') - 1;
                    }
                    $designation = $this->ReadPropertyString('Designation');
                    $month = date('n', $startTime);
                    $monthName = [
                        1  => 'Januar',
                        2  => 'Februar',
                        3  => 'März',
                        4  => 'April',
                        5  => 'Mai',
                        6  => 'Juni',
                        7  => 'Juli',
                        8  => 'August',
                        9  => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Dezember'];
                    $year = date('Y', $startTime);
                    $text = 'Monatsprotokoll ' . $monthName[$month] . ' ' . $year . ', ' . $designation . ":\n\n\n";
                    $messages = AC_GetLoggedValues($archive, $this->GetIDForIdent('MessageArchive'), $startTime, $endTime, 0);
                    if (empty($messages)) {
                        $text .= 'Es sind keine Ereignisse vorhanden.';
                    } else {
                        foreach ($messages as $message) {
                            $text .= $message['Value'] . "\n";
                        }
                    }
                    //Send mail
                    $mailSubject = $this->ReadPropertyString('MonthlyProtocolSubject') . ' ' . $monthName[$month] . ' ' . $year . ', ' . $designation;
                    $recipients = json_decode($this->ReadPropertyString('MonthlyRecipientList'), true);
                    foreach ($recipients as $recipient) {
                        if ($recipient['Use']) {
                            $address = $recipient['Address'];
                            if (strlen($address) >= 6) {
                                @SMTP_SendMailEx($monthlySMTP, $recipient['Address'], $mailSubject, $text);
                            } else {
                                $this->SendDebug(__FUNCTION__, 'Abbruch, E-Mail Adresse hat weniger als 6 Zeichen!', 0);
                            }
                        }
                    }
                }
            }
        }
        $this->SetTimerInterval('SendMonthlyProtocol', $this->GetInterval('MonthlyProtocolTime'));
    }

    /**
     * Sends the archive protocol via mail.
     *
     * @return void
     * @throws Exception
     */
    public function SendArchiveProtocol(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseArchiveProtocol')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Archivprotokoll ist nicht aktiviert!', 0);
            return;
        }
        $archiveSMTP = $this->ReadPropertyInteger('ArchiveSMTP');
        if ($archiveSMTP > 1 && @IPS_ObjectExists($archiveSMTP)) { //0 = main category, 1 = none
            //Prepare data
            //Set start time to 2000-01-01 12:00 am
            $startTime = 946684800;
            $endTime = time();
            $designation = $this->ReadPropertyString('Designation');
            $text = 'Archivprotokoll' . ' ' . $designation . ":\n\n\n";
            $messages = AC_GetLoggedValues($this->ReadPropertyInteger('Archive'), $this->GetIDForIdent('MessageArchive'), $startTime, $endTime, 0);
            if (empty($messages)) {
                $text .= 'Es sind keine Ereignisse vorhanden.';
            } else {
                foreach ($messages as $message) {
                    $text .= $message['Value'] . "\n";
                }
            }
            //Send mail
            $mailSubject = $this->ReadPropertyString('ArchiveProtocolSubject') . ' ' . $designation;
            $recipients = json_decode($this->ReadPropertyString('ArchiveRecipientList'), true);
            foreach ($recipients as $recipient) {
                if ($recipient['Use']) {
                    $address = $recipient['Address'];
                    if (strlen($address) >= 6) {
                        @SMTP_SendMailEx($archiveSMTP, $recipient['Address'], $mailSubject, $text);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, E-Mail Adresse hat weniger als 6 Zeichen!', 0);
                    }
                }
            }
        }
    }
}