<?php
namespace App\modules\bookingfrontend\repositories;

use App\Database\Db;
use App\modules\bookingfrontend\models\Document;
use PDO;

class DocumentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    public function getImagesForBuilding(int $buildingId): array
    {
        $sql = "SELECT * FROM bb_document_building
                WHERE owner_id = :buildingId
                AND category IN ('picture_main', 'picture')";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':buildingId', $buildingId, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row)
        {
            return new Document($row);
        }, $results);
    }
}