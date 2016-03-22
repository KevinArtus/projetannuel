<?php
namespace Projet\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Fiche extends MappedSuperClass
{
     /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="Projet\UserBundle\Entity\User", inversedBy="fiche")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * */
    private $user;


     /**
     * Set user
     *
     * @param \Projet\UserBundle\Entity\User $user
     * @return Fiche
     */
    public function setUser(\Projet\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Projet\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Fiche
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }
}
