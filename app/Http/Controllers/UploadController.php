<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carton;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        // Check file exists
        if (!$request->hasFile('file')) {
            return response()->json([
                'message' => 'No file uploaded'
            ], 400);
        }

        $file = $request->file('file');

        // Open CSV file
        $handle = fopen($file, "r");

        // Skip header row
        fgetcsv($handle);

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

            Carton::create([
                'ID1' => $row[0],
                'ID' => $row[1],
                'Type1' => $row[2],
                'Id_carton' => $row[3],
                'Source1' => $row[4],
                'Supplier1' => $row[5],
                'Long1' => $row[6],
                'Width1' => $row[7],
                'Gramage1' => $row[8],
                'Sheet_count1' => $row[9],
                'Out_Date' => $row[10],
                'Out_ord_num' => $row[11],
                'note_crt' => $row[12],
                'Year' => $row[13],
                'Price' => $row[14]
            ]);

        }

        fclose($handle);

        return response()->json([
            'message' => 'Upload successful'
        ]);
    }
}
