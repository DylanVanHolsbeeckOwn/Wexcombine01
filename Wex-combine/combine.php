<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['submit'])) {
    function combineData($xmlData) {
        $xml = simplexml_load_string($xmlData);
        $combinedData = [];
        $combinedData[] = [
            'OrderNo.', 
            'Email',
            'FullName',
            'Bedrijfsnaam', 
            'ShopCountry',
            'City',
            'Street',
            'postalcode',
        ];

        foreach ($xml->ListOfOrderDetails->OrderDetails as $orderDetail) {
            $fullName = (string)$xml->OrderHeader->Sender->NameAddress->ContactFirstName . ' ' . (string)$xml->OrderHeader->Sender->NameAddress->ContactSurName;
            $combinedData[] = [
                'OrderNo.' => (string)$xml->OrderHeader->OrderNo,
                'Email' => (string)$xml->OrderHeader->Sender->NameAddress->ContactEmail,
                'FullName' => $fullName,
                'Bedrijfsnaam' => (string)$xml->OrderHeader->Delivery->NameAddress->Name1,
                'ShopCountry' => (string)$xml->OrderHeader->ShopCountry,
                'City' => (string)$xml->OrderHeader->Delivery->NameAddress->City,
                'Street' => (string)$xml->OrderHeader->Delivery->NameAddress->Street,
                'postalcode' => (string)$xml->OrderHeader->Delivery->NameAddress->PostalCode,
            ];
        }

        return $combinedData;
    }

    function readWEXFile($filename) {
        $data = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }

    function writeWEXFile($filename, $data) {
        $handle = fopen($filename, 'w');
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    function extractSecondListData($xmlData) {
        $xml = simplexml_load_string($xmlData);
        $secondListData = [];
        $secondListData[] = ['ArticleNo1', 'Quantity', 'SellingPrice', 'Subtotal', 'Total'];

        foreach ($xml->ListOfOrderDetails->OrderDetails as $orderDetail) {
            $articleNo = (string)$orderDetail->ArticleNo1;
            $quantity = (int)$orderDetail->Quantity;
            $sellingPrice = (float)$orderDetail->SellingPrice;
            $taxRate = (float)$orderDetail->TaxRate;

            $subtotal = $quantity * $sellingPrice;
            $total = $subtotal / 100 * $taxRate + $sellingPrice;
            $secondListData[] = [$articleNo, $quantity, $sellingPrice, $subtotal, $total];
        }

        return $secondListData;
    }

    function combineWEXData($files) {
        $combinedWEXData = [];
        foreach ($files as $file) {
            $data = readWEXFile($file);
            if (!empty($data)) {
                $combinedWEXData = array_merge($combinedWEXData, $data);
            }
        }

        $headers = ["OrderNo", "Email", "FullName", "Bedrijfsnaam", "ShopCountry", "postalcode", "City", "Street"];
        array_unshift($combinedWEXData, $headers);

        $maxColumns = count($headers);
        foreach ($combinedWEXData as &$row) {
            $row = array_pad($row, $maxColumns, '');
        }

        return $combinedWEXData;
    }

    $wexFiles = $_FILES['wexFiles']['tmp_name'];

    $xmlData = file_get_contents($_FILES['xml']['tmp_name']);

    $combinedData = combineData($xmlData);

    $secondListData = extractSecondListData($xmlData);

    $combinedWEXData = combineWEXData($wexFiles);

    $filename = isset($_POST['filename']) ? $_POST['filename'] : 'combined_orders';
    $excelFilename = $filename . '.xlsx';
    $wexFilename = $filename . '.wex';

    $spreadsheet = new Spreadsheet();
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('Sheet1');
    $sheet1->fromArray($combinedData, NULL, 'A1');

    $sheet2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Sheet2');
    $spreadsheet->addSheet($sheet2);
    $sheet2->fromArray($secondListData, NULL, 'A1');

    $sheet3 = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Sheet3');
    $spreadsheet->addSheet($sheet3);
    $sheet3->fromArray($combinedWEXData, NULL, 'A1');

    $writer = new Xlsx($spreadsheet);
    $tempFilePath = tempnam(sys_get_temp_dir(), 'excel') . '.xlsx';
    $writer->save($tempFilePath);

    writeWEXFile($wexFilename, $combinedWEXData);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $excelFilename . '"');
    header('Cache-Control: max-age=0');
    readfile($tempFilePath);
    unlink($tempFilePath);
    exit;
}
?>
