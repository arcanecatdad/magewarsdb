<?php

namespace AppBundle\Entity;

/**
 * Type
 */
class Type implements \Gedmo\Translatable\Translatable, \Serializable
{
    public function toString() {
		return $this->name;
	}

	public function serialize() {
		return [
				'code' => $this->code,
				'name' => $this->name,
				'position' => $this->position,
				'is_subtype' => $this->isSubtype,
				'side_code' => $this->side ? $this->side->getCode() : null
		];
	}
	
	public function unserialize($serialized) {
		throw new \Exception("unserialize() method unsupported");
	}
	
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;
    
    /**
     * @var string
     */
    private $name;

    /**
     * @var \AppBundle\Entity\Side
     */
    private $side;

    /**
     * @var boolean
     */
    private $isSubtype;

    /**
     * @var \DateTime
     */
    private $dateCreation;
    
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    
    /**
     * @var integer
     */
    private $position;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cards;
    
    private $locale = 'en';

    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->cards = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set text
     *
     * @param string $name
     * @return Type
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getName()
    {
    	return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Type
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Type
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate
     *
     * @param \DateTime $dateUpdate
     *
     * @return Type
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set isSubtype
     *
     * @param boolean $isSubtype
     *
     * @return Type
     */
    public function setIsSubtype($isSubtype)
    {
        $this->isSubtype = $isSubtype;

        return $this;
    }

    /**
     * Get isSubtype
     *
     * @return boolean
     */
    public function getIsSubtype()
    {
        return $this->isSubtype;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return Type
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set side
     *
     * @param \AppBundle\Entity\Side $side
     * @return Card
     */
    public function setSide(\AppBundle\Entity\Side $side = null)
    {
    	$this->side = $side;
    
    	return $this;
    }
    
    /**
     * Get side
     *
     * @return \AppBundle\Entity\Side
     */
    public function getSide()
    {
    	return $this->side;
    }

    /**
     * Add cards
     *
     * @param \AppBundle\Entity\Card $cards
     * @return Type
     */
    public function addCard(\AppBundle\Entity\Card $cards)
    {
    	$this->cards[] = $cards;
    
    	return $this;
    }
    
    /**
     * Remove cards
     *
     * @param \AppBundle\Entity\Card $cards
     */
    public function removeCard(\AppBundle\Entity\Card $cards)
    {
    	$this->cards->removeElement($cards);
    }
    
    /**
     * Get cards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCards()
    {
    	return $this->cards;
    }
    
    public function setTranslatableLocale($locale)
    {
    	$this->locale = $locale;
    }
    
}
