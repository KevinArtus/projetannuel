<?php
namespace Projet\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @UniqueEntity("$libelle")
 */
class FicheMilieu extends MappedSuperClass
{
    /**
     * @ORM\ManyToOne(targetEntity="Projet\AppBundle\Entity\Fiche", inversedBy="ficheMilieuA")
     * @ORM\JoinColumn(name="fiche_id_1", referencedColumnName="id")
     * */
    private $ficheA;

    /**
     * @ORM\ManyToOne(targetEntity="Projet\AppBundle\Entity\Fiche", inversedBy="ficheMilieuB")
     * @ORM\JoinColumn(name="fiche_id_2", referencedColumnName="id")
     * */
    private $ficheB;

    /**
     * Set ficheA
     *
     * @param \Projet\AppBundle\Entity\Fiche $ficheA
     *
     * @return FicheMilieu
     */
    public function setFicheA(\Projet\AppBundle\Entity\Fiche $ficheA = null)
    {
        $this->ficheA = $ficheA;

        return $this;
    }

    /**
     * Get ficheA
     *
     * @return \Projet\AppBundle\Entity\Fiche
     */
    public function getFicheA()
    {
        return $this->ficheA;
    }

    /**
     * Set ficheB
     *
     * @param \Projet\AppBundle\Entity\Fiche $ficheB
     *
     * @return FicheMilieu
     */
    public function setFicheB(\Projet\AppBundle\Entity\Fiche $ficheB = null)
    {
        $this->ficheB = $ficheB;

        return $this;
    }

    /**
     * Get ficheB
     *
     * @return \Projet\AppBundle\Entity\Fiche
     */
    public function getFicheB()
    {
        return $this->ficheB;
    }

}
