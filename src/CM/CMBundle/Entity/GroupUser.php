<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * EntityUser
 *
 * @ORM\Entity
 * @ORM\Table(name="group_user",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={
 *         "group_id", "user_id"
 *     })}
 * )
 */
class GroupUser
{
    use ORMBehaviors\Timestampable\Timestampable;
    
    const JOIN_NO = 0;
    const JOIN_YES = 1;
    const JOIN_REQUEST = 2;
            
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
                
    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="groupUsers")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $group;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userGroups")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $user;

    /**
     * @var boolean
     *
     * @ORM\Column(name="admin", type="boolean")
     */
    private $admin = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_event", type="smallint", nullable=false)
     */
    private $joinEvent = self::JOIN_REQUEST;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_disc", type="smallint", nullable=false)
     */
    private $joinDisc = self::JOIN_REQUEST;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_article", type="smallint", nullable=false)
     */
    private $joinArticle = self::JOIN_REQUEST;
    
    /**
     * @var array
     *
     * @ORM\Column(name="user_tags", type="array", nullable=true)
     */
    private $userTags;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notification", type="boolean")
     */
    private $notification = true;
    
    public function __construct()
    {
        $this->userTags = new ArrayCollection;
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
     * Set entity
     *
     * @param Entity $entity
     * @return EntityUser
     */
    public function setGroup(Group $group = null)
    {
        $this->group = $group;
    
        return $this;
    }

    /**
     * Get entity
     *
     * @return Entity 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set entity
     *
     * @param User $entity
     * @return EntityUser
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get entity
     *
     * @return User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set admin
     *
     * @param boolean $admin
     * @return EntityUser
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    
        return $this;
    }

    /**
     * Get admin
     *
     * @return boolean 
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set status
     *
     * @param integer $joinEvent
     * @return EntityUser
     */
    public function setJoinEvent($status)
    {
        $this->joinEvent = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getJoinEvent()
    {
        return $this->joinEvent;
    }

    /**
     * Set status
     *
     * @param integer $joinEvent
     * @return EntityUser
     */
    public function setJoinDisc($status)
    {
        $this->joinDisc = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getJoinDisc()
    {
        return $this->joinDisc;
    }

    /**
     * Set status
     *
     * @param integer $joinEvent
     * @return EntityUser
     */
    public function setJoinArticle($status)
    {
        $this->joinArticle = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getJoinArticle()
    {
        return $this->joinArticle;
    }
    
    /**
     * Add userTag
     *
     * @param UserTag $userTag
     * @return EntityUser
     */
    public function addUserTags($userTags)
    {
        foreach ($userTags as $userTag) {
            $this->addUserTag($userTags);
        }
        
        return $this;
    }
    
    /**
     * Add userTag
     *
     * @param UserTag $userTag
     * @return EntityUser
     */
    public function addUserTag($userTag)
    {
        if (!$this->userTags->contains($userTag)) {
            $this->userTags[] = $userTag;
        }
        
        return $this;
    }
    
    /**
     * Remove userTag
     *
     * @param UserTag $userTag
     * return EntityUser
     */
    public function removeUserTag($userTag)
    {
        $this->userTags->removeElement($userTag);
    }
    
    /**
     * Get userTags
     *
     * @return array
     */
    public function getUserTags()
    {
        return $this->userTags;
    }

    /**
     * Set notification
     *
     * @param boolean $nototification
     * @return EntityUser
     */
    public function setNotification($notification)
    {
        $this->notification = $notification;
    
        return $this;
    }

    /**
     * Get notification
     *
     * @return boolean 
     */
    public function getNotification()
    {
        return $this->notification;
    }
}