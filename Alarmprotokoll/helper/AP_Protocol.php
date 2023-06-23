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
     * @return void
     * @throws Exception
     */
    public function GenerateTextFileCustomData(string $StartDate, string $EndDate): void
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
        $dataset = $this->FetchData($startTime, $endTime);
        if (empty($dataset)) {
            $rows .= 'Es sind keine Einträge vorhanden!';
        }
        if (count($dataset) == 1) {
            if (array_key_exists(0, $dataset)) {
                if (array_key_exists('Value', $dataset[0])) {
                    if ($dataset[0]['Value'] == '') {
                        $rows .= 'Es sind keine Einträge vorhanden!';
                    }
                }
            }
        }
        foreach ($dataset as $data) {
            $rows .= $data['Value'] . "\n";
        }
        //Create text file
        $this->CreateTextFile($rows);
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
                //Generate data for textfile
                $this->GenerateTextFileData($startTime, $endTime);
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
        if ($monthlySMTP > 1 && @IPS_ObjectExists($monthlySMTP)) {
            $mailSubject = $this->ReadPropertyString('MonthlyProtocolSubject');
            $mailText = $this->ReadPropertyString('MonthlyProtocolText') . "\n\n";
            $filename = IPS_GetKernelDir() . 'media/' . $this->InstanceID . '/Protokoll.txt';
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
     * Create the text file with content.
     *
     * @param string $Content
     * @return void
     */
    private function CreateTextFile(string $Content): void
    {
        $path = IPS_GetKernelDir() . 'media/' . $this->InstanceID;
        //Check for existing dir
        if (!is_dir($path)) {
            //Create dir
            mkdir($path, 0777, true);
        }
        $fp = fopen($path . '/Protokoll.txt', 'w');
        fwrite($fp, $Content);
        fclose($fp);
    }

    /**
     * Deletes the text file and parent directory.
     *
     * @param int $ID
     * @return void
     */
    private function DeleteTextFile(int $ID): void
    {
        //Check for existing file
        $file = IPS_GetKernelDir() . 'media/' . $ID . '/Protokoll.txt';
        if (file_exists($file)) {
            //Delete file
            unlink($file);
        }
        //Delete dir
        $path = IPS_GetKernelDir() . 'media/' . $ID;
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * Creates a text file with data, data range is from a given start time to a given end time.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return void
     * @throws Exception
     */
    private function GenerateTextFileData(int $StartTime, int $EndTime): void
    {
        //Header
        $title = $this->ReadPropertyString('TextFileTitle');
        $description = $this->ReadPropertyString('TextFileDescription');
        $period = date('d.m.Y', $StartTime) . ' bis ' . date('d.m.Y', $EndTime);
        $rows = $title . "\n\n";
        $rows .= $description . "\n\n";
        $rows .= $period . "\n\n";
        //Data
        $dataset = $this->FetchData($StartTime, $EndTime);
        if (empty($dataset)) {
            $rows .= 'Es sind keine Einträge vorhanden!';
        }
        if (count($dataset) == 1) {
            if (array_key_exists(0, $dataset)) {
                if (array_key_exists('Value', $dataset[0])) {
                    if ($dataset[0]['Value'] == '') {
                        $rows .= 'Es sind keine Einträge vorhanden!';
                    }
                }
            }
        }
        foreach ($dataset as $data) {
            $rows .= $data['Value'] . "\n";
        }
        //Create text file
        $this->CreateTextFile($rows);
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