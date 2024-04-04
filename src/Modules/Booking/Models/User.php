<?php

namespace App\Modules\Booking\Models;


/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public  $id;
    
    /**
     * @ORM\Column(type="string", length=255)
     * 
     * */
	public  $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
	public $email;

	public function __construct($data = [])
	{
		if (!empty($data)) {
			$this->populate($data);
		}
	}

	//getters and setters
	public function getId()
	{
		return $this->id;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}

	public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }
 
    // ... other methods (optional)

    public function populate(array $data)
    {
		if (isset($data['id'])) {
			$this->setId($data['id']);
		}
  
		if (isset($data['name'])) {
            $this->setName($data['name']);
        }

        if (isset($data['email'])) {
            $this->setEmail($data['email']);
        }

        // ... repeat for other properties ...
    }
}
