<?php

namespace App\modules\bookingfrontend\services;

use App\modules\bookingfrontend\repositories\DocumentRepository;
use App\modules\bookingfrontend\models\Document;
class DocumentService
{
    private $documentRepository;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
    }

    /**
     * @return Document[]
     */
    public function getImagesForBuilding(int $buildingId): array
    {
        return $this->documentRepository->getImagesForBuilding($buildingId);
    }
}
