<?php

namespace App\modules\bookingfrontend\models;
//use App\modules\phpgwapi\services\Settings;
use App\modules\bookingfrontend\traits\SerializableTrait;


/**
 * @ORM\Entity
 * @ORM\Table(name="bb_building")
 * @Exclude
 */
class Building
{
    use SerializableTrait;
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=150)
     * @Expose
     */
    public $name;

    /**
     * @ORM\Column(type="text")
     * @Exclude
     */
    public $homepage;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $email;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    public $active;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $street;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $zip_code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $district;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $location_code;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    public $deactivate_calendar;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    public $deactivate_application;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    public $deactivate_sendmessage;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    public $tilsyn_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $tilsyn_email;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    public $tilsyn_phone;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Expose(groups={"admin"})
     */
    public $calendar_text;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    public $tilsyn_name2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $tilsyn_email2;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    public $tilsyn_phone2;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    public $extra_kalendar;

    /**
     * @ORM\Column(type="integer")
     */
    public $activity_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $opening_hours;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    public $description_json;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    public function populate(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->homepage = $data['homepage'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->email = $data['email'] ?? null;
        $this->active = isset($data['active']) ? (int)$data['active'] : 1;
        $this->street = $data['street'] ?? '';
        $this->zip_code = $data['zip_code'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->district = $data['district'] ?? '';
        $this->location_code = $data['location_code'] ?? null;
        $this->deactivate_calendar = isset($data['deactivate_calendar']) ? (int)$data['deactivate_calendar'] : 0;
        $this->deactivate_application = isset($data['deactivate_application']) ? (int)$data['deactivate_application'] : 0;
        $this->deactivate_sendmessage = isset($data['deactivate_sendmessage']) ? (int)$data['deactivate_sendmessage'] : 0;
        $this->tilsyn_name = $data['tilsyn_name'] ?? null;
        $this->tilsyn_email = $data['tilsyn_email'] ?? null;
        $this->tilsyn_phone = $data['tilsyn_phone'] ?? null;
        $this->calendar_text = $data['calendar_text'] ?? null;
        $this->tilsyn_name2 = $data['tilsyn_name2'] ?? null;
        $this->tilsyn_email2 = $data['tilsyn_email2'] ?? null;
        $this->tilsyn_phone2 = $data['tilsyn_phone2'] ?? null;
        $this->extra_kalendar = isset($data['extra_kalendar']) ? (int)$data['extra_kalendar'] : 0;
        $this->activity_id = $data['activity_id'] ?? null;
        $this->opening_hours = $data['opening_hours'] ?? null;
        $this->description_json = $data['description_json'] ?? null;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'homepage' => $this->homepage,
            'phone' => $this->phone,
            'email' => $this->email,
            'active' => $this->active,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'district' => $this->district,
            'location_code' => $this->location_code,
            'deactivate_calendar' => $this->deactivate_calendar,
            'deactivate_application' => $this->deactivate_application,
            'deactivate_sendmessage' => $this->deactivate_sendmessage,
            'tilsyn_name' => $this->tilsyn_name,
            'tilsyn_email' => $this->tilsyn_email,
            'tilsyn_phone' => $this->tilsyn_phone,
            'calendar_text' => $this->calendar_text,
            'tilsyn_name2' => $this->tilsyn_name2,
            'tilsyn_email2' => $this->tilsyn_email2,
            'tilsyn_phone2' => $this->tilsyn_phone2,
            'extra_kalendar' => $this->extra_kalendar,
            'activity_id' => $this->activity_id,
            'opening_hours' => $this->opening_hours,
            'description_json' => $this->description_json,
        ];
    }
}