<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity_category_translation",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={
 *         "translatable_id", "locale"
 *     })}
 * )
 */
class EntityCategoryTranslation
{
    use ORMBehaviors\Sluggable\Sluggable;
    use ORMBehaviors\Translatable\Translation;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="plural", type="string", length=50)
     */
    private $plural;
    
    public function __toString()
    {
        return $this->name;
    }

    public static function className()
    {
        return get_class();
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
     * Set name
     *
     * @param string $name
     * @return CategoryTranslation
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set plural
     *
     * @param string $plural
     * @return CategoryTranslation
     */
    public function setPlural($plural)
    {
        $this->plural = $plural;
    
        return $this;
    }

    /**
     * Get plural
     *
     * @return string 
     */
    public function getPlural()
    {
        return $this->plural;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return CategoryTranslation
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    
        return $this;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get sluggable fields
     * 
     * @access public
     * @return void
     */
    public function getSluggableFields()
    {
        return ['name'];
    }
}
