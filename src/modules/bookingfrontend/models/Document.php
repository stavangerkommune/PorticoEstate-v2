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

    public const CATEGORY_PICTURE = 'picture';
    public const CATEGORY_REGULATION = 'regulation';
    public const CATEGORY_HMS_DOCUMENT = 'HMS_document';
    public const CATEGORY_PICTURE_MAIN = 'picture_main';
    public const CATEGORY_DRAWING = 'drawing';
    public const CATEGORY_PRICE_LIST = 'price_list';
    public const CATEGORY_OTHER = 'other';


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
     * @OA\Property(type="string", enum={"picture", "regulation", "HMS_document", "picture_main", "drawing", "price_list", "other"})
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

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_PICTURE,
            self::CATEGORY_REGULATION,
            self::CATEGORY_HMS_DOCUMENT,
            self::CATEGORY_PICTURE_MAIN,
            self::CATEGORY_DRAWING,
            self::CATEGORY_PRICE_LIST,
            self::CATEGORY_OTHER,
        ];
    }
}