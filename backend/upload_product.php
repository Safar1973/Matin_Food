<?php
header("Content-Type: application/json; charset=utf-8");

$targetDir = "../../uploads/";
$fileName = time() . "_" . basename($_FILES["image"]["name"]);
$targetFile = $targetDir . $fileName;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    echo json_encode(["image" => $fileName]);
} else {
    echo json_encode(["error" => "Upload failed"]);
}
