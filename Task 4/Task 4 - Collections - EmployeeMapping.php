<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Task4Controller extends Controller
{
    public function index()
    {
        $employees = [
            ['name' => 'John', 'city' => 'Dallas'],
            ['name' => 'Jane', 'city' => 'Austin'],
            ['name' => 'Jake', 'city' => 'Dallas'],
            ['name' => 'Jill', 'city' => 'Dallas'],
        ];

        $offices = [
            ['office' => 'Dallas HQ', 'city' => 'Dallas'],
            ['office' => 'Dallas South', 'city' => 'Dallas'],
            ['office' => 'Austin Branch', 'city' => 'Austin'],
        ];

        // We are convert array into collections
        $employeesCollection = collect($employees);
        $officesCollection = collect($offices);

        // Group employees by city and then extract the names from each employee groups
        $employeesByCity = $employeesCollection->groupBy('city')
            ->map(function ($cityEmployees) {
                return $cityEmployees->pluck('name')->toArray();
            });

        // Generate the output structure and then map each office name to employees in its city
        $output = $officesCollection->groupBy('city')
            ->map(function ($cityOffices, $city) use ($employeesByCity) {
                return $cityOffices->mapWithKeys(function ($office) use ($city, $employeesByCity) {
                    return [
                        $office['office'] => $employeesByCity[$city] ?? []
                    ];
                });
            });

        return response()->json($output);
    }
}