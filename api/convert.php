<?php
header('Content-Type: application/json');

require_once '../vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser;

// Increase execution time for heavy tasks like AI background removal (first run downloads models)
set_time_limit(300);

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    try {
        $file = $_FILES['file'];
        $format = $_POST['format'] ?? null;
        $type = $_POST['type'] ?? 'image';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error code: ' . $file['error']);
        }

        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        // Clean up old files
        $files = glob($uploadDir . '*');
        $now = time();
        foreach ($files as $f) {
            if (is_file($f)) {
                if ($now - filemtime($f) >= 3600)
                    unlink($f);
            }
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to move uploaded file.');
        }

        // Unified Output Filename Logic: [OriginalName]_converted.[format]
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        // Sanitize original name heavily to prevent issues
        $originalName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
        $outputName = $originalName . '_converted.' . $format;
        $outputFile = $uploadDir . $outputName;

        // --- IMAGE CONVERSION ---
        if ($type === 'image') {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($filePath);

            if ($format === 'rembg') {
                // Background Removal using Python 'rembg' tool
                // Requires: pip install rembg[cli]
                $outputName = $originalName . '_no_bg.png';
                $outputFile = $uploadDir . $outputName;

                // Escape paths
                $cmdInput = escapeshellarg($filePath);
                $cmdOutput = escapeshellarg($outputFile);

                // Run via custom worker script to avoid CLI module issues
                $workerPath = __DIR__ . '/rembg_worker.py';
                $cmd = "python3 " . escapeshellarg($workerPath) . " $cmdInput $cmdOutput 2>&1";
                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0 || !file_exists($outputFile)) {
                    // Fallback error message (usually implies missing dependency)
                    $errorOut = implode("\n", $output);
                    throw new Exception("Background removal failed. details: " . $errorOut . " Hint: Run `pip install rembg` in your terminal.");
                }

            } elseif (in_array($format, ['jpg', 'jpeg'])) {
                $image->toJpeg()->save($outputFile);
            } elseif ($format === 'png') {
                $image->toPng()->save($outputFile);
            } elseif ($format === 'webp') {
                $image->toWebp()->save($outputFile);
            } else {
                throw new Exception('Unsupported image format: ' . $format);
            }
        }

        // --- DATA CONVERSION ---
        elseif ($type === 'data') {
            $spreadsheet = IOFactory::load($filePath);

            if ($format === 'xlsx') {
                $writer = new Xlsx($spreadsheet);
                $writer->save($outputFile);
            } elseif ($format === 'csv') {
                $writer = new Csv($spreadsheet);
                $writer->save($outputFile);
            } else {
                throw new Exception('Unsupported data format');
            }
        }

        // --- DOCUMENT CONVERSION ---
        elseif ($type === 'doc') {

            if ($format === 'pdf') {
                // ... same logic as before for standard types ...
                // But now we can try to support DOCX -> PDF too? 
                // PhpOffice doesn't do DOCX -> PDF cleanly without unoconv/mpdf tricks.
                // Keeping original HTML/TXT -> PDF logic for stability + safety.

                $inputExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $content = '';

                if (in_array(strtolower($inputExt), ['txt', 'html', 'php'])) {
                    $content = file_get_contents($filePath);
                    if (strtolower($inputExt) === 'txt') {
                        $content = nl2br(htmlspecialchars($content));
                        $content = "<html><body><pre style='font-family: sans-serif'>$content</pre></body></html>";
                    }
                } else {
                    // For now fallback or throw
                    throw new Exception('To PDF supports only Text/HTML inputs currently.');
                }

                $dompdf = new Dompdf();
                $dompdf->loadHtml($content);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($outputFile, $dompdf->output());
            } elseif ($format === 'docx') {
                // Text/HTML -> DOCX
                $phpWord = new PhpWord();
                $section = $phpWord->addSection();

                $content = file_get_contents($filePath);
                // Simple text dump
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $section->addText($line);
                }

                $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($outputFile);
            } elseif ($format === 'txt') {
                // PDF -> EXTRACT TEXT
                $parser = new Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();

                file_put_contents($outputFile, $text);
            } else {
                throw new Exception('Unsupported doc format');
            }
        }

        // --- AUDIO/VIDEO CONVERSION ---
        elseif ($type === 'av') {
            // Locate FFMPEG
            $ffmpegPath = trim(shell_exec('python3 -c "import imageio_ffmpeg; print(imageio_ffmpeg.get_ffmpeg_exe())"'));
            if (!$ffmpegPath || !file_exists($ffmpegPath)) {
                // Fallback attempt or error
                // Try 'ffmpeg' in path
                $ffmpegPath = trim(shell_exec('which ffmpeg'));
                if (!$ffmpegPath) {
                    throw new Exception('FFmpeg binary not found. Please install imageio-ffmpeg python package or ffmpeg system-wide.');
                }
            }

            $inputPath = escapeshellarg($filePath);
            $outputPath = escapeshellarg($outputFile);

            if ($format === 'mp3') {
                // Extract Audio: Encode to MP3, ignore video
                // -vn: disable video
                // -acodec libmp3lame or just -f mp3
                // Simple: -q:a 0 (best variable bit rate) -map a
                $cmd = "$ffmpegPath -y -i $inputPath -vn -acodec libmp3lame -q:a 2 $outputPath 2>&1";
                exec($cmd, $output, $returnCode);
            } elseif ($format === 'mp4') {
                // Audio to Video: Black screen
                // -f lavfi -i color=c=black:s=1280x720:r=5
                // -shortest (end when audio ends)
                $cmd = "$ffmpegPath -y -f lavfi -i color=c=black:s=1280x720:r=5 -i $inputPath -c:v libx264 -tune stillimage -c:a aac -b:a 192k -pix_fmt yuv420p -shortest $outputPath 2>&1";
                exec($cmd, $output, $returnCode);
            } else {
                throw new Exception('Unsupported AV format');
            }

            if ($returnCode !== 0) {
                $errorOut = implode("\n", $output);
                throw new Exception("Conversion failed: " . $errorOut);
            }
        } else {
            throw new Exception('Invalid conversion type.');
        }

        $response['success'] = true;
        $response['download_url'] = 'uploads/' . basename($outputFile);
        $response['file_name'] = $outputName; // Send clean name (e.g., file_converted.docx)

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
