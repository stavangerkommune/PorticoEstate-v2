<?php

namespace App\modules\bookingfrontend\models;

use App\traits\SerializableTrait;

/**
 * @OA\Schema(
 *      schema="Document",
 *      type="object",
 *      title="Document",
 *      description="Document model representing images and other files",
 * )
 * @Exclude
 */
class Document
{
    use SerializableTrait;

    /**
     * @OA\Property(type="integer")
     * @Expose
     */
    public $id;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $name;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $description;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $category;

    /**
     * @OA\Property(type="integer")
     * @Expose
     */
    public $owner_id;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $url;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->owner_id = $data['owner_id'] ?? null;
        $this->url = "/bookingfrontend/download/{$this->id}";
    }
}