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
            //Prepare data
            $archive = $this->ReadPropertyInteger('Archive');
            if ($archive != 0) {
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
                //Generate Report
                $this->GenerateReport($startTime, $endTime);
                //Send report
                $this->SendReport();
            }
        }
        $this->SetTimerInterval('SendMonthlyProtocol', $this->GetInterval('MonthlyProtocolTime'));
    }

    /**
     * Sends the report to the email recipients.
     *
     * @return void
     * @throws Exception
     */
    public function SendReport(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        $designation = $this->ReadPropertyString('Designation');
        $monthlySMTP = $this->ReadPropertyInteger('MonthlySMTP');
        $mailSubject = $this->ReadPropertyString('MonthlyProtocolSubject') . ' ' . $designation;
        $mailText = $this->ReadPropertyString('MonthlyProtocolText');
        $mediaID = $this->GetIDForIdent('ReportPDF');
        $filename = IPS_GetKernelDir() . IPS_GetMedia($mediaID)['MediaFile'];
        if ($monthlySMTP > 1 && @IPS_ObjectExists($monthlySMTP)) { //0 = main category, 1 = none
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
}