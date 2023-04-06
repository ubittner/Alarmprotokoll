<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Protocol.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpExpressionResultUnusedInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait AP_Protocol
{
    /**
     * Creates a text file with data, data range is a user-defined time period.
     *
     * @param string $StartDate
     * @param string $EndDate
     * @return bool
     * @throws Exception
     */
    public function CreateTextFileCustomPeriod(string $StartDate, string $EndDate): bool
    {
        //Start date
        $start = json_decode($StartDate);
        $startDay = $start->day;
        $startMonth = $start->month;
        $startYear = $start->year;
        $startTime = strtotime($startDay . '.' . $startMonth . '.' . $startYear . ' 00:00:00');
        //End date
        $end = json_decode($EndDate);
        $endDay = $end->day;
        $endMonth = $end->month;
        $endYear = $end->year;
        $endTime = strtotime($endDay . '.' . $endMonth . '.' . $endYear . ' 23:59:59');
        //Header
        $title = $this->ReadPropertyString('TextFileTitle');
        $description = $this->ReadPropertyString('TextFileDescription');
        $period = date('d.m.Y', $startTime) . ' bis ' . date('d.m.Y', $endTime);
        $rows = $title . "\n\n";
        $rows .= $description . "\n\n";
        $rows .= $period . "\n\n";
        //Data
        foreach ($this->FetchData($startTime, $endTime) as $data) {
            $rows .= $data['Value'] . "\n";
        }
        //Set content
        return IPS_SetMediaContent($this->GetIDForIdent('TextFile'), base64_encode($rows));
    }

    /**
     * Sends the monthly protocol via email.
     *
     * @param bool $CheckDay
     * false =  don't check day
     * true =   check day
     *
     * @param int $ProtocolPeriod
     * 0 =  actual month
     * 1 =  previous month
     * 2 =  month before previous month
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
        //Check if it is the correct day of the month
        $day = date('j');
        if ($day == $this->ReadPropertyInteger('MonthlyProtocolDay') || !$CheckDay) {
            if ($this->ReadPropertyInteger('Archive') != 0) {
                //Actual month
                $startTime = strtotime('first day of this month midnight');
                $endTime = strtotime('first day of next month midnight') - 1;
                //Previous month
                if ($ProtocolPeriod == 1) {
                    $startTime = strtotime('first day of previous month midnight');
                    $endTime = strtotime('first day of this month midnight') - 1;
                }
                //Two month before actual month
                if ($ProtocolPeriod == 2) {
                    $startTime = strtotime('first day of ' . date('F', strtotime('-2 month', strtotime(date('F') . '1'))) . ' ' . date('Y', strtotime('-2 month', strtotime(date('F') . '1'))));
                    $endTime = strtotime('first day of ' . date('F', strtotime('-1 month', strtotime(date('F') . '1'))) . ' ' . date('Y', strtotime('-1 month', strtotime(date('F') . '1')))) - 1;
                }
                //Create text file
                $this->CreateTextFile($startTime, $endTime);
                //Send protocol
                $this->SendProtocol();
            }
        }
        $this->SetTimerInterval('SendMonthlyProtocol', $this->GetInterval('MonthlyProtocolTime'));
    }

    /**
     * Sends the protocol to the email recipients.
     *
     * @return void
     * @throws Exception
     */
    public function SendProtocol(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $monthlySMTP = $this->ReadPropertyInteger('MonthlySMTP');
        if ($monthlySMTP > 1 && @IPS_ObjectExists($monthlySMTP)) { //0 = main category, 1 = none
            $mailSubject = $this->ReadPropertyString('MonthlyProtocolSubject');
            $mailText = $this->ReadPropertyString('MonthlyProtocolText') . "\n\n";
            $filename = IPS_GetKernelDir() . IPS_GetMedia($this->GetIDForIdent('TextFile'))['MediaFile'];
            $recipients = json_decode($this->ReadPropertyString('MonthlyRecipientList'), true);
            foreach ($recipients as $recipient) {
                if ($recipient['Use']) {
                    $address = $recipient['Address'];
                    if (strlen($address) >= 6) {
                        @SMTP_SendMailAttachmentEx($monthlySMTP, $recipient['Address'], $mailSubject, $mailText, $filename);
                    } else {
                        $this->SendDebug(__FUNCTION__, 'Abbruch, E-Mail Adresse hat weniger als 6 Zeichen!', 0);
                    }
                }
            }
        }
    }

    #################### Private

    /**
     * Creates a text file with data, data range is from a given start time to a given end time.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return bool
     * @throws Exception
     */
    private function CreateTextFile(int $StartTime, int $EndTime): bool
    {
        //Header
        $title = $this->ReadPropertyString('TextFileTitle');
        $description = $this->ReadPropertyString('TextFileDescription');
        $period = date('d.m.Y', $StartTime) . ' bis ' . date('d.m.Y', $EndTime);
        $rows = $title . "\n\n";
        $rows .= $description . "\n\n";
        $rows .= $period . "\n\n";
        //Data
        foreach ($this->FetchData($StartTime, $EndTime) as $data) {
            $rows .= $data['Value'] . "\n";
        }
        //Set content
        return IPS_SetMediaContent($this->GetIDForIdent('TextFile'), base64_encode($rows));
    }

    /**
     * Registers a media document.
     *
     * @param string $Ident
     * @param string $Name
     * @param string $Extension
     * @param int $Position
     * @return void
     */
    private function RegisterMediaDocument(string $Ident, string $Name, string $Extension, int $Position = 0): void
    {
        $this->RegisterMedia(5 /* Document */, $Ident, $Name, $Extension, $Position);
    }

    /**
     * Registers media.
     *
     * @param int $Type
     * @param string $Ident
     * @param string $Name
     * @param string $Extension
     * @param int $Position
     * @return bool
     */
    private function RegisterMedia(int $Type, string $Ident, string $Name, string $Extension, int $Position): bool
    {
        $result = true;
        $mid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        if ($mid === false) {
            $mid = IPS_CreateMedia($Type);
            IPS_SetParent($mid, $this->InstanceID);
            IPS_SetIdent($mid, $Ident);
            IPS_SetName($mid, $Name);
            IPS_SetPosition($mid, $Position);
            IPS_SetHidden($mid, true);
            $result = IPS_SetMediaFile($mid, 'media/Alarmprotokoll_(ID ' . $mid . ').' . $Extension, false);
        }
        return $result;
    }

    /**
     * Fetches the data from the archive.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return array
     * @throws Exception
     */
    private function FetchData(int $StartTime, int $EndTime): array
    {
        $result = [];
        $archiveID = $this->ReadPropertyInteger('Archive');
        if ($archiveID <= 1 || @!IPS_ObjectExists($archiveID)) {
            return $result;
        }
        $variableID = $this->GetIDForIdent('MessageArchive');
        if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
            if (AC_GetLoggingStatus($archiveID, $variableID)) {
                $result = AC_GetLoggedValues($this->ReadPropertyInteger('Archive'), $variableID, $StartTime, $EndTime, 0);
            }
        }
        return $result;
    }
}