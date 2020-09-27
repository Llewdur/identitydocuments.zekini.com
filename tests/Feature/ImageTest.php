<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageTest extends TestCase
{
    public function testCheckIdentity()
    {
        $dataArray = [
            'image' => UploadedFile::fake()->image('storage/sample.jpg'),
        ];

        $this->post('/api/images/checkIdentity', $dataArray)
            ->assertStatus(200);
    }

    public function testDetectText()
    {
        $dataArray = [
            'image' => UploadedFile::fake()->image('storage/sample.jpg'),
        ];

        $this->post('/api/images/detectText', $dataArray)
            ->assertStatus(200);
    }
}
