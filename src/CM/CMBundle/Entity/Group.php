<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Group
 *
 * @ORM\Entity(repositoryClass="GroupRepository")
 * @ORM\Table(name="`group`")
 * @ORM\HasLifecycleCallbacks
 */
class Group
{
    use ORMBehaviors\Sluggable\Sluggable;
    use \CM\CMBundle\Model\ImageAndCoverTrait;
    
    const TYPE_DUO = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     **/
    private $creator;

    /**
     * @ORM\OneToMany(targetEntity="GroupUser", mappedBy="group", cascade={"persist", "remove"})
     */
    private $groupUsers;
        
    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="group", cascade={"persist", "remove"})
	 */
	private $posts;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=250, nullable=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="vip", type="boolean")
     */
    private $vip = false;
        
    /**
     * @ORM\OneToMany(targetEntity="Image", mappedBy="group", cascade={"persist", "remove"})
     */
    private $images;
    
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->images = new ArrayCollection();
    }
	
	public function __toString()
	{
    	return $this->getName();
	}

    protected function getRootDir()
    {
        return __DIR__.'/../Resources/public/';
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
     * Set typeId
     *
     * @param integer $typeId
     * @return Group
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get typeId
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set user
     *
     * @param User $user
     * @return Like
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return User 
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param \CM\CMBundle\Entity\EntityUser $comment
     * @return Entity
     */
    public function addGroupUser(
        User $user,
        $admin = false,
        $joinEvent = GroupUser::JOIN_REQUEST,
        $joinDisc = GroupUser::JOIN_REQUEST,
        $joinArticle = GroupUser::JOIN_REQUEST,
        $notification = true,
        $userTags = array()
    )
    {
        $groupUser = new GroupUser;
        $groupUser->setGroup($this)
            ->setUser($user)
            ->setAdmin($admin)
            ->setJoinEvent($joinEvent)
            ->setJoinDisc($joinDisc)
            ->setJoinArticle($joinArticle)
            ->addUserTags($userTags)
            ->setNotification($notification);
        $this->groupUsers[] = $groupUser;
    
        return $this;
    }

    /**
     * @param \CM\CMBundle\Entity\EntityUser $users
     */
    public function removeGroupUser(GroupUser $groupUser)
    {
        $this->groupUsers->removeElement($groupUser);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroupUsers()
    {
        return $this->groupUsers;
    }

    /**
     * @param \CM\CMBundle\Entity\Image $images
     * @return Entity
     */
    public function addPost(Post $post)
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setGroup($this);
        }
    
        return $this;
    }

    /**
     * @param \CM\CMBundle\Entity\Image $images
     */
    public function removePost(Post $post)
    {
        $this->posts->removeElement($post);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Group
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set vip
     *
     * @param boolean $vip
     * @return Group
     */
    public function setVip($vip)
    {
        $this->vip = $vip;
    
        return $this;
    }

    /**
     * Get vip
     *
     * @return boolean 
     */
    public function getVip()
    {
        return $this->vip;
    }

    /**
     * @param User $comment
     * @return Entity
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addUserGroup($this);
        }
    
        return $this;
    }

    /**
     * @param User $users
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param \CM\CMBundle\Entity\Image $comment
     * @return Entity
     */
    public function addImage(Image $image)
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setUser($this);
        }
    
        return $this;
    }

    /**
     * @param Image $images
     */
    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getImages()
    {
        return $this->images;
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