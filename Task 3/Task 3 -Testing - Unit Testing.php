<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcessProductImage;
use App\Models\Product;
use App\Services\SpreadsheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SpreadsheetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $spreadsheetService;
    protected $importerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->importerMock = Mockery::mock('importer');
        app()->instance('importer', $this->importerMock);
        
        $this->spreadsheetService = new SpreadsheetService();
    }

    public function test_process_spreadsheet_creates_products_and_dispatches_jobs()
    {
        // Arrange
        Queue::fake();
        
        $testData = [
            [
                'product_code' => 'ABC123',
                'quantity' => 10
            ],
            [
                'product_code' => 'DEF456',
                'quantity' => 20
            ]
        ];
        
        $this->importerMock->shouldReceive('import')
            ->once()
            ->with('test/file/path.xlsx')
            ->andReturn($testData);
        
        // Act
        $this->spreadsheetService->processSpreadsheet('test/file/path.xlsx');
        
        // Assert
        $this->assertDatabaseHas('products', [
            'code' => 'ABC123',
            'quantity' => 10
        ]);
        
        $this->assertDatabaseHas('products', [
            'code' => 'DEF456',
            'quantity' => 20
        ]);
        
        $this->assertEquals(2, Product::count());
        
        Queue::assertPushed(ProcessProductImage::class, 2);
        Queue::assertPushed(ProcessProductImage::class, function ($job) {
            return $job->product->code === 'ABC123';
        });
        Queue::assertPushed(ProcessProductImage::class, function ($job) {
            return $job->product->code === 'DEF456';
        });
    }

    public function test_process_spreadsheet_skips_invalid_data()
    {
        // Arrange
        Queue::fake();
        
        $testData = [
            [
                'product_code' => 'ABC123',
                'quantity' => 10
            ],
            [
                'product_code' => '', // Invalid: missing product code
                'quantity' => 20
            ],
            [
                'product_code' => 'GHI789',
                'quantity' => -5 // Invalid: negative quantity
            ]
        ];
        
        $this->importerMock->shouldReceive('import')
            ->once()
            ->with('test/file/path.xlsx')
            ->andReturn($testData);
        
        // Act
        $this->spreadsheetService->processSpreadsheet('test/file/path.xlsx');
        
        // Assert
        $this->assertDatabaseHas('products', [
            'code' => 'ABC123',
            'quantity' => 10
        ]);
        
        $this->assertDatabaseMissing('products', [
            'quantity' => 20
        ]);
        
        $this->assertDatabaseMissing('products', [
            'code' => 'GHI789'
        ]);
        
        $this->assertEquals(1, Product::count());
        
        Queue::assertPushed(ProcessProductImage::class, 1);
    }

    public function test_process_spreadsheet_handles_duplicate_product_codes()
    {
        // Arrange
        Queue::fake();
        
        // Create a product first
        Product::create([
            'code' => 'ABC123',
            'quantity' => 5
        ]);
        
        $testData = [
            [
                'product_code' => 'ABC123', // Duplicate code
                'quantity' => 10
            ],
            [
                'product_code' => 'DEF456',
                'quantity' => 20
            ]
        ];
        
        $this->importerMock->shouldReceive('import')
            ->once()
            ->with('test/file/path.xlsx')
            ->andReturn($testData);
        
        // Act
        $this->spreadsheetService->processSpreadsheet('test/file/path.xlsx');
        
        // Assert
        $this->assertDatabaseHas('products', [
            'code' => 'ABC123',
            'quantity' => 5 // Original value, not updated
        ]);
        
        $this->assertDatabaseHas('products', [
            'code' => 'DEF456',
            'quantity' => 20
        ]);
        
        $this->assertEquals(2, Product::count());
        
        Queue::assertPushed(ProcessProductImage::class, 1);
        Queue::assertPushed(ProcessProductImage::class, function ($job) {
            return $job->product->code === 'DEF456';
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}