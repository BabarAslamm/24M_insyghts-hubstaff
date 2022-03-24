<?php

namespace Insyghts\Hubstaff\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Insyghts\Hubstaff\Models\ActivityLog;
use Insyghts\Hubstaff\Models\ActivityScreenShot;
use ZipArchive;
use Insyghts\Hubstaff\Helpers\Helpers;
use Symfony\Component\HttpFoundation\File\File;

class ActivityScreenShotService
{
    function __construct(
        ActivityLog $aLog,
        ActivityScreenShot $aScreenShot,
        HubstaffServerService $serverService
    ) {
        $this->aLog = $aLog;
        $this->aScreenShot = $aScreenShot;
        $this->serverService = $serverService;
        $this->serverTimestamp =  $this->serverService->getTimestamp();
        $this->serverTimeString = $this->serverTimestamp['data'];
    }

    private function getBase64Zips($path)
    {
        $results = [];
        $zip = new ZipArchive();

        $res = $zip->open($path);
        if ($res == TRUE) {
            $zip->extractTo(Helpers::get_public_path('base64'));
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $result = [];
                $result['filename'] = $zip->getNameIndex($i);
                $result['filepath'] = Helpers::get_public_path('base64') . DIRECTORY_SEPARATOR . $zip->getNameIndex($i);
                array_push($results, $result);
                echo "hi";
            }
        }
        $zip->close();

        $this->deleteTempFiles(Helpers::get_public_path('files/*'));



        return $results;
    }

    private function extractBase64Zips($zipsArr, &$response)
    {
        $results = [];
        ob_end_clean();
        $folderName = Helpers::get_public_path('string64');
        if (!is_dir($folderName)) {
            mkdir($folderName, 0777);
        }
        foreach ($zipsArr as $zipArr) {
            $string64Name = "string64" . time();
            $stringFile = $this->un_gzip($zipArr['filepath'], Helpers::get_public_path('string64') . DIRECTORY_SEPARATOR . $string64Name, $response);
            $result = [];
            $result['filename'] = $string64Name;
            $result['filepath'] = $stringFile;
            array_push($results, $result);
        }
        return $results;
    }

    private function un_gzip($gz_filename, $output_filename = null, &$response, $allow_overwrite = false, $read_chunk_length = 10000)
    {
        //error check zipped file
        if (!$gz_filename) {
            // return un_gzip_error('Can’t unzip without a filename.');
            $response['success'] = false;
            $response['data'] = "Can't unzip without a filename.";
        }
        // if (strtolower(substr($gz_filename,-3)) != '.gz') { return un_gzip_error('The provided filename does not have the expected .gz extension.'); }
        if (!file_exists($gz_filename)) {
            // return un_gzip_error('The zipped file does not exist.');
            $response['success'] = false;
            $response['data'] = "The zipped file does not exist.";
        }

        //error check output file
        if (!$output_filename) {
            $output_filename = substr($gz_filename, 0, -3);
        } //just drop the .gz from incoming file by default
        if ((!$allow_overwrite) && file_exists($output_filename)) {
            // return un_gzip_error('A file already exists at the output file location.');
            $response['success'] = false;
            $response['data'] = "A file already exists at the output file location.";
        }
        if (file_exists($output_filename) && (!is_writable($output_filename))) {
            // return un_gzip_error('The output file location is not writeable.');
            $response['success'] = false;
            $response['data'] = "The output file location is not writeable.";
        }

        //open the files
        $gz = gzopen($gz_filename, 'rb');
        if (!$gz) {
            // return un_gzip_error('The zipped file cannot be opened for reading.');
            $response['success'] = false;
            $response['data'] = "The zipped file cannot be opened for reading.";
        }
        $out = fopen($output_filename, 'wb');
        if (!$out) {
            // return un_gzip_error('The output file cannot be opened for writing.');
            $response['success'] = false;
            $response['data'] = "The output file cannot be opened for writing.";
        }

        //keep unzipping $read_chunk_length bytes at a time until we hit the end of the file
        while (!gzeof($gz)) {
            $unzipped = gzread($gz, $read_chunk_length);
            if (fwrite($out, $unzipped) === false) {
                // return un_gzip_error('There was an error writing to the output file.');
                $response['success'] = false;
                $response['data'] = "There was an error writing to the output file.";
            }
        }

        //close the files
        gzclose($gz);
        fclose($out);

        //return the output filename
        return $output_filename;
    }

    private function convertBase64Image($string64Arr)
    {
        $results = [];
        foreach ($string64Arr as $string64) {
            $contentString = file_get_contents($string64['filepath']);
            $output_file = Helpers::get_public_path('screenshots') . DIRECTORY_SEPARATOR . "temp.jpeg";
            $outputFile = $this->base64_to_image($contentString, $output_file);
            array_push($results, $output_file);
            // echo '<pre>'; print_r(file_get_contents($string64['filepath'])); exit;
        }
        return $results;
    }

    private function base64_to_image($base64_string, $output_file)
    {
        // open the output file for writing
        $ifp = fopen($output_file, 'wb');

        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode(',', $base64_string);

        // we could add validation here with ensuring count( $data ) > 1
        fwrite($ifp, $data[1]);

        // clean up the file resource
        fclose($ifp);

        echo '<img src="' . $output_file . '" >';

        return $output_file;
    }


    private function deleteTempFiles($dir)
    {
        $files = glob($dir); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }
    }



    public function saveActivityScreenShot($data, $actLog)
    {
        $response = [
            'success' => 0,
            'data'   => 'There is some error'
        ];
        $bulk_insert = [];
        try {
            // zip file extraction
            if (!empty($data['screen_shots'])) {

                // echo '<pre>'; print_r($data['screen_shots']);
                // $myFile = file_get_contents();
                // $fpath = Helpers::get_public_path('screenshots' . DIRECTORY_SEPARATOR . '1644412750prof.png');
                // $fname = '1644412750prof.png';
                // $upFile = new UploadedFile($fpath, $fname);
                // echo '<pre>'; print_r($upFile->getClientOriginalName()); exit;

                $name = time() . '.' . $data['screen_shots']->extension();
                $path = $data['screen_shots']->move(Helpers::get_public_path('files'), $name);
                $user_id = app('loginUser')->getUser()->id;
                $base64Zips = $this->getbase64Zips($path);

                $base64String = $this->extractBase64Zips($base64Zips, $response);
                
                
                // $r = $this->bunzip2($base64String[0]['filepath'], Helpers::get_public_path('screenshots') . DIRECTORY_SEPARATOR . "temp.jpeg");
                // $r = $this->decompress($base64String[0]['filepath']);
                // $r = $this->decompress('D:\GrayMatrix\insights\Capture_0_20220321092240.zip');

               
                echo '<pre>';
                print_r("ok");
                exit;

                $zip = new ZipArchive();
                $res = $zip->open($path);
                if ($res == TRUE) {
                    $zip->extractTo(Helpers::get_public_path('base64'));

                    $zip->close();
                    // delete that zip file now
                    unlink($path);

                    exit;
                    for ($i = 0; $i < $zip->numFiles; $i++) {

                        // $imageData = $this->convertBase64();
                        exit;
                        $imgName = $zip->getNameIndex($i);
                        // rename this image
                        $oldPath = Helpers::get_public_path('screenshots' . DIRECTORY_SEPARATOR . $imgName);
                        // file name
                        $imgName = time() . $imgName;
                        $newPath = Helpers::get_public_path('screenshots' . DIRECTORY_SEPARATOR . $imgName);
                        // renamed image with path
                        $renamed = rename($oldPath, $newPath);
                        if ($renamed) {
                            // file path
                            $imgPath = $newPath;
                            $imgObject = new UploadedFile($imgPath, $imgName);
                            $created_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
                            $updated_at = gmdate('Y-m-d G:i:s', $this->serverTimeString);
                            $s3Path = 'screenshots' . DIRECTORY_SEPARATOR . $actLog->user_id . DIRECTORY_SEPARATOR . gmdate('Y-m-d', strtotime($actLog->activity_date)) . DIRECTORY_SEPARATOR . $imgName;
                            $row = [
                                'user_id' => $actLog->user_id,
                                'session_token_id' => $actLog->session_token_id,
                                'activity_log_id' => $actLog->id,
                                'image_path' => $s3Path,
                                'created_by' => $user_id,
                                'last_modified_by' => $user_id,
                                'deleted_by' => NULL,
                                'created_at' => $created_at,
                                'updated_at' => $updated_at
                            ];
                            if ($this->uploadToS3($s3Path, $imgObject)) {
                                array_push($bulk_insert, $row);
                                unlink($imgPath);
                            } else {
                                echo "<script>alert('Some Error While Uploading to S3')</script>";
                            }
                        }
                    }

                    $result = $this->aScreenShot->saveRecord($bulk_insert);
                    if ($result) {
                        $response['success'] = 1;
                        $response['data'] = "Successfully Inserted";
                    }
                }
            }
        } catch (Exception $e) {
            $show = get_class($e) == 'Illuminate\Database\QueryException' ? false : true;
            if ($show) {
                $response['data'] = $e->getMessage();
            }
        } finally {
            return $response;
        }
    }

    public function uploadToS3($path, $photo)
    {
        try {
            $path = Storage::disk('s3')->put($path, file_get_contents($photo), 'public');
            return $path;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
