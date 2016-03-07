<?php

namespace Projet\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Projet\AppBundle\Entity\Fiche;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * Map controller.
 *
 */
class MapController extends Controller
{

    /**
     * Affichage de la carte
     *
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render(
            'ProjetAppBundle:Map:index.html.twig', array()
        );
    }
}