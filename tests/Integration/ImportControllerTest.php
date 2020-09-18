<?php

namespace Tests\Integration;

use App\Models\Organisation;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    private function createSpreadsheet($maxRows = 20)
    {
        /** Create a new Spreadsheet Object **/
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'name');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'description');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'url');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'email');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'phone');

        return $spreadsheet;
    }

    private function writeSpreadsheetToDisk($spreadsheet)
    {

        $xlsxWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $xlsWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xls");

        try {
            $xlsxWriter->save(storage_path('app/stubs/spreadsheet.xlsx'));
            $xlsWriter->save(storage_path('app/stubs/spreadsheet.xls'));
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            dump($e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_validates_and_rejects_rows_in_imported_data()
    {
        Storage::disk('local')->makeDirectory('stubs');
        $organisations = factory(Organisation::class, 5)->create();
        $spreadsheet = $this->createSpreadsheet();

        $row = 1;
        foreach ($organisations as $organisation) {
            $row++;
            $spreadsheet->getActiveSheet()->setCellValue('A' . $row, $organisation->name);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $row, $organisation->description);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $row, rand(0, 1) ? $organisation->url : '');
            $spreadsheet->getActiveSheet()->setCellValue('D' . $row, rand(0, 1) ? $organisation->email : '');
            $spreadsheet->getActiveSheet()->setCellValue('E' . $row, rand(0, 1) ? $organisation->phone : '');
        }

        $this->writeSpreadsheetToDisk($spreadsheet);

        $spreadsheet = new UploadedFile(storage_path('app/stubs/spreadsheet.xls'), 'spreadsheet.xls', 'application/vnd.ms-excel', null);

        $response = $this->post('/core/v1/organisations/import', ['spreadsheet' => $spreadsheet], ['Accept' => 'application/json']);

        $response->assertStatus(Response::HTTP_OK);

        Storage::disk('local')->deleteDirectory('stubs');
    }
}
