<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TaskController extends Controller
{
    public function task1Csv()
    {
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $row = [];
            for ($j = 0; $j < 52; $j++) {
                $row[] = mt_rand() / mt_getrandmax();
            }
            $data[] = $row;
        }
        
        $cumulativeSums = [];
        foreach ($data as $individualIndex => $individualData) {
            $cumulativeSums[$individualIndex] = [];
            $sum = 0;
            foreach ($individualData as $weekIndex => $value) {
                $sum += $value;
                $cumulativeSums[$individualIndex][$weekIndex] = $sum;
            }
        }
        
        $csvRows = [];
        
        $headerRow = ['Individual'];
        for ($week = 1; $week <= 52; $week++) {
            $headerRow[] = "Week $week";
        }
        $csvRows[] = $headerRow;
        
        foreach ($cumulativeSums as $individualIndex => $weeks) {
            $row = ["Individual " . ($individualIndex + 1)];
            
            foreach ($weeks as $value) {
                $row[] = number_format($value, 4, '.', '');
            }
            
            $csvRows[] = $row;
        }
        
        $output = fopen('php://temp', 'r+');
        foreach ($csvRows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cumulative_sums.csv"',
        ];
        
        return Response::make($csvContent, 200, $headers);
    }
}