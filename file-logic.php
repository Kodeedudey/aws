<?php
// connect to the database
$conn = mysqli_connect('intellpaat.cnquihxmepqn.us-east-1.rds.amazonaws.com', 'intellipaat', 'intellipaat123', 'upload');

$sql = "SELECT * FROM files";
$result = mysqli_query($conn, $sql);

$files = mysqli_fetch_all($result, MYSQLI_ASSOC);

$bucket = 'intellipaat1';

// Uploads files
if (isset($_POST['save'])) { // if save button on the form is clicked
    // name of the uploaded file
    $filename = $_FILES['myfile']['name'];

    // destination of the file on the server
    $destination = 'uploads/' . $filename;
    // get the file extension
    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    // the physical file on a temporary uploads directory on the server
    $file = $_FILES['myfile']['tmp_name'];
    $size = $_FILES['myfile']['size'];
    if (!in_array($extension, ['zip', 'pdf', 'docx', 'jpg'])) {
        echo "You file extension must be .zip, .pdf, .docx or .jpg";
    } elseif ($_FILES['myfile']['size'] > 1000000) { // file shouldn't be larger than 1Megabyte
        echo "File too large!";
    } else {
        // move the uploaded (temporary) file to the specified destination
        if (move_uploaded_file($file, $destination)) {
            $sql = "INSERT INTO files (name, size, downloads, location) VALUES ('$filename', $size, 0, 'uploads/$filename')";
            if (mysqli_query($conn, $sql)) {    
                echo "File uploaded successfully";
            }
        } else {
            echo "Failed to upload file.";
        }
    }
}

    // upload files to S3
    if (isset($_POST['save'])){
        // name of the uploaded file
        $filename = $_FILES['myfile']['name'];
    
        // destination of the file on the server
        $destination = 'uploads/' . $filename;
        
        require 'C:\xampp\htdocs\aws\aws\aws-autoloader.php';
    
        $s3 = new Aws\S3\S3Client([
            'region'  => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => "AKIA2ESXROHT7NXERYWZ",
                'secret' => "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",
        ]
            ]);
    
            // Send a PutObject request and get the result object.
            $key = $filename;
    
            $result = $s3->putObject([
            'Bucket' => 'intellipaat1',
            'Key'    => $key,
            'SourceFile' => $destination
    ]);
    
    }

if (isset($_GET['file_id'])) {
    $id = $_GET['file_id'];

    // fetch file to download from database
    $sql = "SELECT * FROM files WHERE id=$id";
    $result = mysqli_query($conn, $sql);

    $file = mysqli_fetch_assoc($result);
    $filepath = 'uploads/' . $file['name'];

    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize('uploads/' . $file['name']));
        readfile('uploads/' . $file['name']);

        // Now update downloads count
        $newCount = $file['downloads'] + 1;
        $updateQuery = "UPDATE files SET downloads=$newCount WHERE id=$id";
        mysqli_query($conn, $updateQuery);
        exit;
    }
}