<?php

namespace App\modules\bookingfrontend\models;

use App\traits\SerializableTrait;
use App\modules\bookingfrontend\helpers\UserHelper;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model"
 * )
 * @Exclude
 */
class User
{
    use SerializableTrait;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $ssn;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $orgnr;

    /**
     * @OA\Property(type="string")
     * @Expose
     */
    public $orgname;

    /**
     * @OA\Property(type="integer")
     * @Expose
     */
    public $org_id;

    /**
     * @OA\Property(type="boolean")
     * @Expose
     */
    public $is_logged_in;

    public function __construct(UserHelper $userHelper)
    {
        $this->ssn = $userHelper->ssn;
        $this->orgnr = $userHelper->orgnr;
        $this->orgname = $userHelper->orgname;
        $this->org_id = $userHelper->org_id;
        $this->is_logged_in = $userHelper->is_logged_in();
    }
}