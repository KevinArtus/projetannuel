<?php

namespace Projet\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Projet\AppBundle\Entity\Fiche;
use Projet\AppBundle\Form\FicheType;

class FicheController extends Controller
{

    /**
     *
     * @Template()
     */
    public function dashboardAction()
    {
        $em = $this->getDoctrine()->getManager();

        $fiche = $em->getRepository('ProjetApptBundle:Fiche')->findAll();

        return array(
            'fiche' => $fiche,
        );
    }

    /**
     * Creates a form to create a Partner entity.
     *
     * @param Fiche $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Fiche $entity)
    {
        $form = $this->createForm(new FicheType(), $entity, array(
            'action' => $this->generateUrl('projet_app_fiche_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Créer une fiche'));

        return $form;
    }

    /**
     * Displays a form to create a new Fiche entity.
     *
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Fiche();
        $form = $this->createCreateForm($entity);
        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a new Event entity.
     * @Method("POST")
     * @Template("MiriadeEventBundle:Event:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Fiche();

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('miriade_event_event_dashboard'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }
}
