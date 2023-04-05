<?php

/**
 * @project       Alarmprotokoll/Alarmprotokoll
 * @file          AP_Report.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection HtmlDeprecatedAttribute */

declare(strict_types=1);

include_once __DIR__ . '/../../libs/vendor/autoload.php';
trait AP_Report
{
    /**
     * Generates a report.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return bool
     * @throws Exception
     */
    public function GenerateReport(int $StartTime, int $EndTime): bool
    {
        $pdfContent = $this->GeneratePDF(
            'IP-Symcon ' . IPS_GetKernelVersion(),
            'Alarmprotokoll',
            'Alarmprotokoll',
            $this->GenerateHTML($StartTime, $EndTime),
            'report.pdf');

        $mediaID = $this->GetIDForIdent('ReportPDF');
        return IPS_SetMediaContent($mediaID, base64_encode($pdfContent));
    }

    /**
     * Generates a custom report.
     *
     * @param string $StartDate
     * @param string $EndDate
     * @return bool
     * @throws Exception
     */
    public function GenerateCustomReport(string $StartDate, string $EndDate): bool
    {
        $this->SendDebug(__FUNCTION__, 'Von: ' . $StartDate, 0);
        $this->SendDebug(__FUNCTION__, 'Bis: ' . $EndDate, 0);

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

        //Generate
        $pdfContent = $this->GeneratePDF(
            'IP-Symcon ' . IPS_GetKernelVersion(),
            'Alarmprotokoll',
            'Alarmprotokoll',
            $this->GenerateHTML($startTime, $endTime),
            'report.pdf');

        $mediaID = $this->GetIDForIdent('ReportPDF');
        return IPS_SetMediaContent($mediaID, base64_encode($pdfContent));
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
            $result = IPS_SetMediaFile($mid, 'media/' . $mid . '.' . $Extension, false);
        }
        return $result;
    }

    /**
     * Fetches the data from archive.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return array
     * @throws Exception
     */
    private function FetchData(int $StartTime, int $EndTime): array
    {
        $archiveID = $this->ReadPropertyInteger('Archive');
        if ($archiveID <= 1 || @!IPS_ObjectExists($archiveID)) {
            return [];
        }
        return AC_GetLoggedValues($this->ReadPropertyInteger('Archive'), $this->GetIDForIdent('MessageArchive'), $StartTime, $EndTime, 0);
    }

    /**
     * Generates the HTML header.
     *
     * @return string
     * @throws Exception
     */
    private function GenerateHTMLHeader(): string
    {
        $imageData = $this->ReadPropertyString('LogoData');
        if ($imageData == '') {
            $header = '';
        } else {
            $header = '
            <table cellpadding="5" cellspacing="0" border="0" width="95%">
            <tr>
                <td width="20%"><img src="@' . $imageData . '" alt=""></td>
            </tr>
        </table>
        ';
        }
        return $header;
    }

    /**
     * Generates the HTML rows.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return string
     * @throws Exception
     */
    private function GenerateHTMLRows(int $StartTime, int $EndTime): string
    {
        $rows = '';
        foreach ($this->FetchData($StartTime, $EndTime) as $data) {
            $rows .= '
                <tr>
	                <td style="text-align: left;">' . $data['Value'] . '</td>
                </tr>
            ';
        }
        return $rows;
    }

    /**
     * Generates HTML content.
     *
     * @param int $StartTime
     * @param int $EndTime
     * @return string
     * @throws Exception
     */
    private function GenerateHTML(int $StartTime, int $EndTime): string
    {
        $header = $this->GenerateHTMLHeader();
        $title = 'Alarmanlage ' . $this->ReadPropertyString('Designation');
        $description = 'Alarmprotokoll';
        $period = date('d.m.Y', $StartTime) . ' bis ' . date('d.m.Y', $EndTime);
        $rows = $this->GenerateHTMLRows($StartTime, $EndTime);
        if ($rows == '') {
            $rows .= '
                <tr>
	                <td style="text-align: left;">Es sind keine Ereignisse vorhanden.</td>
                </tr>
            ';
        }
        return
            $header .
            '<br/>
                <h2>' . $title . '</h2>   
                <h4>' . $description . ' ' . $period . '</h4>
                <h5> </h5>         
                <table cellpadding="5" cellspacing="0" border="0" width="95%">
                    <tr style="background-color: #cccccc; padding:5px;">
                       <td style="padding:5px;" width="100%"><b>Ereignisse</b></td>
                    </tr>' . $rows . '<tr>
                        <td colspan="5"><hr/></td>
                    </tr>
                </table>
            <br/>';
    }

    /**
     * Generates a pdf document.
     *
     * @param $author
     * @param $title
     * @param $subject
     * @param $html
     * @param $filename
     * @return string
     */
    private function GeneratePDF($author, $title, $subject, $html, $filename): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($author);
        $pdf->SetTitle($title);
        $pdf->SetSubject($subject);

        $pdf->setPrintHeader(false);

        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP - 15, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);

        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true);

        return $pdf->Output($filename, 'S');
    }
}