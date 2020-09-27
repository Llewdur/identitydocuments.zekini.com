<?php

namespace App\Http\Controllers;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    protected $imageAnnotator;

    private $fileName;

    private $image;

    public function __construct(Request $request)
    {
        $this->fileName = $request->image ?? 'storage/sample.jpg';
        $this->image = file_get_contents($this->fileName);

        $this->imageAnnotator = new ImageAnnotatorClient(
            ['credentials' => config('google_key')]
        );

        $this->checkIdentity();
        $this->detect_text();
        $this->detect_text_gcs();
        $this->detect_document_text();
        $this->detect_crop_hints();
        $this->detect_face('storage/babsie.jpg');
        $this->detect_image_property();
        $this->detect_label();
        $this->detect_landmark();
        $this->detect_logo();
        $this->detect_object();
        $this->detect_safe_search();
    }

    public function __destruct()
    {
        $this->imageAnnotator->close();
    }

    public function checkIdentity()
    {
        $response = $this->imageAnnotator->labelDetection($this->image);
        $labels = $response->getLabelAnnotations();

        if ($labels) {
            echo 'Labels:' . PHP_EOL;
            foreach ($labels as $label) {
                echo $label->getDescription() . PHP_EOL;
            }
        } else {
            echo 'No label found' . PHP_EOL;
        }
    }

    public function detect_text()
    {
        $response = $this->imageAnnotator->textDetection($this->image);
        $texts = $response->getTextAnnotations();

        printf('%d texts found:' . PHP_EOL, count($texts));
        foreach ($texts as $text) {
            print $text->getDescription() . PHP_EOL;

            # get bounds
            $vertices = $text->getBoundingPoly()->getVertices();
            $bounds = [];
            foreach ($vertices as $vertex) {
                $bounds[] = sprintf('(%d,%d)', $vertex->getX(), $vertex->getY());
            }
            print 'Bounds: ' . join(', ', $bounds) . PHP_EOL;
        }
    }

    public function detect_text_gcs()
    {
        $response = $this->imageAnnotator->textDetection($this->image);
        $texts = $response->getTextAnnotations();

        printf('%d texts found:' . PHP_EOL, count($texts));
        foreach ($texts as $text) {
            print $text->getDescription() . PHP_EOL;

            # get bounds
            $vertices = $text->getBoundingPoly()->getVertices();
            $bounds = [];
            foreach ($vertices as $vertex) {
                $bounds[] = sprintf('(%d,%d)', $vertex->getX(), $vertex->getY());
            }
            print 'Bounds: ' . join(', ', $bounds) . PHP_EOL;
        }

        if ($error = $response->getError()) {
            print 'API Error: ' . $error->getMessage() . PHP_EOL;
        }
    }

    public function detect_document_text()
    {
        $response = $this->imageAnnotator->documentTextDetection($this->image);
        $annotation = $response->getFullTextAnnotation();

        # print out detailed and structured information about document text
        if ($annotation) {
            foreach ($annotation->getPages() as $page) {
                foreach ($page->getBlocks() as $block) {
                    $block_text = '';
                    foreach ($block->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getWords() as $word) {
                            foreach ($word->getSymbols() as $symbol) {
                                $block_text .= $symbol->getText();
                            }
                            $block_text .= ' ';
                        }
                        $block_text .= "\n";
                    }
                    printf('Block content: %s', $block_text);
                    printf('Block confidence: %f' . PHP_EOL,
                        $block->getConfidence());

                    # get bounds
                    $vertices = $block->getBoundingBox()->getVertices();
                    $bounds = [];
                    foreach ($vertices as $vertex) {
                        $bounds[] = sprintf('(%d,%d)', $vertex->getX(),
                            $vertex->getY());
                    }
                    print 'Bounds: ' . join(', ', $bounds) . PHP_EOL;
                    print PHP_EOL;
                }
            }
        } else {
            print 'No text found' . PHP_EOL;
        }
    }

    public function detect_crop_hints()
    {
        $response = $this->imageAnnotator->cropHintsDetection($this->image);
        $annotations = $response->getCropHintsAnnotation();

        # print the crop hints from the annotation
        if ($annotations) {
            print 'Crop hints:' . PHP_EOL;
            foreach ($annotations->getCropHints() as $hint) {
                # get bounds
                $vertices = $hint->getBoundingPoly()->getVertices();
                $bounds = [];
                foreach ($vertices as $vertex) {
                    $bounds[] = sprintf('(%d,%d)', $vertex->getX(),
                        $vertex->getY());
                }
                print 'Bounds: ' . join(', ', $bounds) . PHP_EOL;
            }
        } else {
            print 'No crop hints' . PHP_EOL;
        }
    }

    public function detect_face($outFile = null)
    {
        $response = $this->imageAnnotator->faceDetection($this->image);
        $faces = $response->getFaceAnnotations();

        # names of likelihood from google.cloud.vision.enums
        $likelihoodName = [
            'UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY',
            'POSSIBLE', 'LIKELY', 'VERY_LIKELY', ];

        printf('%d faces found:' . PHP_EOL, count($faces));
        foreach ($faces as $face) {
            $anger = $face->getAngerLikelihood();
            printf('Anger: %s' . PHP_EOL, $likelihoodName[$anger]);

            $joy = $face->getJoyLikelihood();
            printf('Joy: %s' . PHP_EOL, $likelihoodName[$joy]);

            $surprise = $face->getSurpriseLikelihood();
            printf('Surprise: %s' . PHP_EOL, $likelihoodName[$surprise]);

            # get bounds
            $vertices = $face->getBoundingPoly()->getVertices();
            $bounds = [];
            foreach ($vertices as $vertex) {
                $bounds[] = sprintf('(%d,%d)', $vertex->getX(), $vertex->getY());
            }
            print 'Bounds: ' . join(', ', $bounds) . PHP_EOL;
            print PHP_EOL;
        }

        # draw box around faces
        if ($faces && $outFile) {
            $imageCreateFunc = [
                'png' => 'imagecreatefrompng',
                'gd' => 'imagecreatefromgd',
                'gif' => 'imagecreatefromgif',
                'jpg' => 'imagecreatefromjpeg',
                'jpeg' => 'imagecreatefromjpeg',
            ];
            $imageWriteFunc = [
                'png' => 'imagepng',
                'gd' => 'imagegd',
                'gif' => 'imagegif',
                'jpg' => 'imagejpeg',
                'jpeg' => 'imagejpeg',
            ];

            copy($this->fileName, $outFile);
            $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
            if (! array_key_exists($ext, $imageCreateFunc)) {
                throw new \Exception('Unsupported image extension');
            }
            $outputImage = call_user_func($imageCreateFunc[$ext], $outFile);

            foreach ($faces as $face) {
                $vertices = $face->getBoundingPoly()->getVertices();
                if ($vertices) {
                    $x1 = $vertices[0]->getX();
                    $y1 = $vertices[0]->getY();
                    $x2 = $vertices[2]->getX();
                    $y2 = $vertices[2]->getY();
                    imagerectangle($outputImage, $x1, $y1, $x2, $y2, 0x00ff00);
                }

                call_user_func($imageWriteFunc[$ext], $outputImage, $outFile);
                printf('Output image written to %s' . PHP_EOL, $outFile);
            }
        }
    }

    public function detect_image_property()
    {
        # annotate the image
        $image = file_get_contents($this->fileName);
        $response = $this->imageAnnotator->imagePropertiesDetection($image);
        $props = $response->getImagePropertiesAnnotation();

        print 'Properties:' . PHP_EOL;
        foreach ($props->getDominantColors()->getColors() as $colorInfo) {
            printf('Fraction: %s' . PHP_EOL, $colorInfo->getPixelFraction());
            $color = $colorInfo->getColor();
            printf('Red: %s' . PHP_EOL, $color->getRed());
            printf('Green: %s' . PHP_EOL, $color->getGreen());
            printf('Blue: %s' . PHP_EOL, $color->getBlue());
            print PHP_EOL;
        }
    }

    public function detect_label()
    {
        $response = $this->imageAnnotator->labelDetection($this->image);
        $labels = $response->getLabelAnnotations();

        if ($labels) {
            print 'Labels:' . PHP_EOL;
            foreach ($labels as $label) {
                print $label->getDescription() . PHP_EOL;
            }
        } else {
            print 'No label found' . PHP_EOL;
        }
    }

    public function detect_landmark()
    {
        $response = $this->imageAnnotator->landmarkDetection($this->image);
        $landmarks = $response->getLandmarkAnnotations();

        printf('%d landmark found:' . PHP_EOL, count($landmarks));
        foreach ($landmarks as $landmark) {
            print $landmark->getDescription() . PHP_EOL;
        }
    }

    public function detect_logo()
    {
        $response = $this->imageAnnotator->logoDetection($this->image);
        $logos = $response->getLogoAnnotations();

        printf('%d logos found:' . PHP_EOL, count($logos));
        foreach ($logos as $logo) {
            print $logo->getDescription() . PHP_EOL;
        }
    }

    public function detect_object()
    {
        $response = $this->imageAnnotator->objectLocalization($this->image);
        $objects = $response->getLocalizedObjectAnnotations();

        foreach ($objects as $object) {
            $name = $object->getName();
            $score = $object->getScore();
            $vertices = $object->getBoundingPoly()->getNormalizedVertices();

            printf('%s (confidence %f)):' . PHP_EOL, $name, $score);
            print 'normalized bounding polygon vertices: ';
            foreach ($vertices as $vertex) {
                printf(' (%f, %f)', $vertex->getX(), $vertex->getY());
            }
            print PHP_EOL;
        }
    }

    public function detect_safe_search()
    {
        $response = $this->imageAnnotator->safeSearchDetection($this->image);
        $safe = $response->getSafeSearchAnnotation();

        $adult = $safe->getAdult();
        $medical = $safe->getMedical();
        $spoof = $safe->getSpoof();
        $violence = $safe->getViolence();
        $racy = $safe->getRacy();

        # names of likelihood from google.cloud.vision.enums
        $likelihoodName = [
            'UNKNOWN', 'VERY_UNLIKELY', 'UNLIKELY',
            'POSSIBLE', 'LIKELY', 'VERY_LIKELY', ];

        printf('Adult: %s' . PHP_EOL, $likelihoodName[$adult]);
        printf('Medical: %s' . PHP_EOL, $likelihoodName[$medical]);
        printf('Spoof: %s' . PHP_EOL, $likelihoodName[$spoof]);
        printf('Violence: %s' . PHP_EOL, $likelihoodName[$violence]);
        printf('Racy: %s' . PHP_EOL, $likelihoodName[$racy]);
    }

    public function detect_web()
    {
        $response = $this->imageAnnotator->webDetection($this->image);
        $web = $response->getWebDetection();

        // Print best guess labels
        printf('%d best guess labels found' . PHP_EOL,
            count($web->getBestGuessLabels()));
        foreach ($web->getBestGuessLabels() as $label) {
            printf('Best guess label: %s' . PHP_EOL, $label->getLabel());
        }
        print PHP_EOL;

        // Print pages with matching images
        printf('%d pages with matching images found' . PHP_EOL,
            count($web->getPagesWithMatchingImages()));
        foreach ($web->getPagesWithMatchingImages() as $page) {
            printf('URL: %s' . PHP_EOL, $page->getUrl());
        }
        print PHP_EOL;

        // Print full matching images
        printf('%d full matching images found' . PHP_EOL,
            count($web->getFullMatchingImages()));
        foreach ($web->getFullMatchingImages() as $fullMatchingImage) {
            printf('URL: %s' . PHP_EOL, $fullMatchingImage->getUrl());
        }
        print PHP_EOL;

        // Print partial matching images
        printf('%d partial matching images found' . PHP_EOL,
            count($web->getPartialMatchingImages()));
        foreach ($web->getPartialMatchingImages() as $partialMatchingImage) {
            printf('URL: %s' . PHP_EOL, $partialMatchingImage->getUrl());
        }
        print PHP_EOL;

        // Print visually similar images
        printf('%d visually similar images found' . PHP_EOL,
            count($web->getVisuallySimilarImages()));
        foreach ($web->getVisuallySimilarImages() as $visuallySimilarImage) {
            printf('URL: %s' . PHP_EOL, $visuallySimilarImage->getUrl());
        }
        print PHP_EOL;

        // Print web entities
        printf('%d web entities found' . PHP_EOL,
            count($web->getWebEntities()));
        foreach ($web->getWebEntities() as $entity) {
            printf('Description: %s, Score %s' . PHP_EOL,
                $entity->getDescription(),
                $entity->getScore());
        }
    }
}
