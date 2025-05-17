<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=UTF-8');


require_once 'config.php';       

const TABLE = 'books';            

function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}


function withEncodedCover(?array $row): ?array {
    if ($row && !empty($row['book_cover'])) {
        $row['book_cover'] = base64_encode($row['book_cover']);
    }
    return $row;
}


$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {


        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM " . TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                $row = withEncodedCover($stmt->fetch(PDO::FETCH_ASSOC));
                jsonResponse($row ?: []);
            }

            $rows = $pdo->query("SELECT * FROM " . TABLE . " ORDER BY id DESC")
                         ->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_map('withEncodedCover', $rows);
            jsonResponse($rows);
            break;

        case 'DELETE':
            if (!$id) jsonResponse(['error' => 'Missing id'], 400);

            $pdo->prepare("DELETE FROM " . TABLE . " WHERE id = ?")
                ->execute([$id]);
            jsonResponse(['success' => true]);
            break;


        case 'POST':

            $title    = trim($_POST['book_title']    ?? '');
            $author   = trim($_POST['author_name']   ?? '');
            $genre    = trim($_POST['genre']         ?? '');
            $year     = trim($_POST['publication_year'] ?? '');
            $quantity = intval($_POST['quantity']    ?? 0);

            if (!$title || !$author || !$genre || !$year || $quantity < 1) {
                jsonResponse(['error' => 'Incomplete data'], 400);
            }


            if (!preg_match('/^(19|20)\d\d\-(0[1-9]|1[0-2])$/', $year)) {
                jsonResponse(['error' => 'Invalid publication_year (YYYY-MM expected)'], 400);
            }


            $cover = null;
            if (!empty($_FILES['book_cover']['tmp_name'])) {
                $mime = mime_content_type($_FILES['book_cover']['tmp_name']);
                if (str_starts_with($mime, 'image/')) {
                    $cover = file_get_contents($_FILES['book_cover']['tmp_name']);
                }
            }

            if ($id) {   // UPDATE
                if ($cover !== null) {
                    $sql = "UPDATE " . TABLE . " 
                            SET book_title=?, author_name=?, genre=?, publication_year=?, quantity=?, book_cover=?
                            WHERE id=?";
                    $pdo->prepare($sql)->execute([$title,$author,$genre,$year,$quantity,$cover,$id]);
                } else {
                    $sql = "UPDATE " . TABLE . " 
                            SET book_title=?, author_name=?, genre=?, publication_year=?, quantity=?
                            WHERE id=?";
                    $pdo->prepare($sql)->execute([$title,$author,$genre,$year,$quantity,$id]);
                }
                jsonResponse(['updated' => true]);
            } else {     // CREATE
                $sql = "INSERT INTO " . TABLE .
                       " (book_title, author_name, genre, publication_year, quantity, book_cover)
                         VALUES (?,?,?,?,?,?)";
                $pdo->prepare($sql)->execute([$title,$author,$genre,$year,$quantity,$cover]);
                jsonResponse(['inserted_id' => $pdo->lastInsertId()]);
            }
            break;


        default:
            jsonResponse(['error' => 'Method Not Allowed'], 405);
    }

} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
