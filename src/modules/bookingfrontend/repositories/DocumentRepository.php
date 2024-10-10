<?php
namespace App\modules\bookingfrontend\repositories;

use App\Database\Db;
use App\modules\bookingfrontend\models\Document;
use PDO;

class DocumentRepository
{
    private $db;
    private $owner_type;

    public function __construct(string $owner_type = Document::OWNER_BUILDING)
    {
        $this->db = Db::getInstance();
        $this->owner_type = $owner_type;
    }

    public function getDocumentsForBuilding(int $buildingId, array $categories = null): array
    {
        $sql = "SELECT * FROM bb_document_building WHERE owner_id = :buildingId";
        $params = [':buildingId' => $buildingId];

        if ($categories !== null && !empty($categories)) {
            $placeholders = [];
            foreach ($categories as $index => $category) {
                $key = ":category{$index}";
                $placeholders[] = $key;
                $params[$key] = $category;
            }
            $sql .= " AND category IN (" . implode(', ', $placeholders) . ")";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            return new Document($row, $this->owner_type);
        }, $results);
    }

    public function getDocumentById(int $documentId): ?Document
    {
        $sql = "SELECT * FROM bb_document_building WHERE id = :documentId";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':documentId' => $documentId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return new Document($result, $this->owner_type);
    }
}