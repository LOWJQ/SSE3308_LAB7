<?php
class BookRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function list(): array
    {
        $sql  = "SELECT * FROM books ORDER BY id DESC";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'base64Cover'], $rows);
    }

 
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->base64Cover($row) : null;
    }


    public function create(array $data, ?array $file = null): int
    {
        $cover = $this->readCover($file);
        $sql   = "INSERT INTO books
                 (book_title, author_name, genre, publication_year, quantity, book_cover)
                 VALUES (:title, :author, :genre, :pub, :qty, :cover)";

        $this->pdo->prepare($sql)->execute([
            ':title'  => $data['book_title'],
            ':author' => $data['author_name'],
            ':genre'  => $data['genre'],
            ':pub'    => $data['publication_year'],   
            ':qty'    => (int)$data['quantity'],
            ':cover'  => $cover
        ]);
        return (int)$this->pdo->lastInsertId();
    }


    public function update(int $id, array $data, ?array $file = null): bool
    {
        $base = "UPDATE books SET book_title=:title, author_name=:author,
                 genre=:genre, publication_year=:pub, quantity=:qty";
        $params = [
            ':title'  => $data['book_title'],
            ':author' => $data['author_name'],
            ':genre'  => $data['genre'],
            ':pub'    => $data['publication_year'],
            ':qty'    => (int)$data['quantity'],
            ':id'     => $id
        ];

        $cover = $this->readCover($file);
        if ($cover !== null) {
            $base   .= ", book_cover=:cover";
            $params[':cover'] = $cover;
        }

        $sql = $base . " WHERE id=:id";
        return $this->pdo->prepare($sql)->execute($params);
    }


    public function delete(int $id): bool
    {
        return $this->pdo->prepare("DELETE FROM books WHERE id = ?")
                         ->execute([$id]);
    }

    private function readCover(?array $file): ?string
    {
        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $mime = mime_content_type($file['tmp_name']);
            if (str_starts_with($mime, 'image/')) {
                return file_get_contents($file['tmp_name']);
            }
        }
        return null;
    }

    private function base64Cover(array $row): array
    {
        if (!empty($row['book_cover'])) {
            $row['book_cover'] = base64_encode($row['book_cover']);
        }
        return $row;
    }
}
