<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IdentityDocumentsTest extends TestCase
{
    public function testValidate()
    {
        $dataArray = [
            'image' => UploadedFile::fake()->image('storage/babs.jpeg'),
        ];

        $response = $this->post('/api/validate', $dataArray);
        $response->assertStatus(200);
    }
}
