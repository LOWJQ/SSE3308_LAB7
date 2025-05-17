<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'config.php';      
    echo json_encode(['status' => 'success']);
} catch (Throwable $th) {     
    http_response_code(500);
    echo json_encode(['error' => $th->getMessage()]);
}
