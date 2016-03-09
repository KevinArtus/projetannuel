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
use Projet\AppBundle\Form\FicheEditType;

class FicheController extends Controller
{

    /**
     *
     * @Template()
     */
    public function dashboardAction()
    {
        $em = $this->getDoctrine()->getManager();

        $fiche = $em->getRepository('ProjetAppBundle:Fiche')->findAll();

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
     * Creates a new Fiche entity.
     * @Method("POST")
     * @Template("ProjetAppBundle:Fiche:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Fiche();

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        $address = urlencode($entity->getAddress());
        $zipcode = $entity->getZipcode();
        $city = $entity->getCity();

        //Récuperation de la longitude et de la latitude en fonction de l'adresse et du code postal
        $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".$address."".$zipcode."&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
        $json = json_decode($coordpolaire);
        if ($json->{'status'} == "ZERO_RESULTS") {
            $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . $zipcode . "&region=fr&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
            $json = json_decode($coordpolaire);
            if ($json->{'status'} == "OK") {
                $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            } else {
                $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=14000&region=fr&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
                $json = json_decode($coordpolaire);
                $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            }
        } else {
            $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
            $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
        }

        $entity->setUser($this->get('security.context')->getToken()->getUser());
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('projet_app_map_index'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * @Template("ProjetAppBundle:Fiche:liste.html.twig")
     */
    public function listeAction()
    {
        $em = $this->getDoctrine()->getManager();

        $fiches = $em->getRepository('ProjetAppBundle:Fiche')->findBy(array('user' => $this->get('security.context')->getToken()->getUser()));

        return array(
            'fiches' => $fiches,
        );
    }

    /**
     * Displays a form to edit an existing Customer entity.
     *
     * @Route("/{id}/edit", name="customer_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $fiche = $em->getRepository('ProjetAppBundle:Fiche')->find($id);

        if (!$fiche) {
            throw $this->createNotFoundException('Unable to find Fiche entity.');
        }

        $editForm = $this->createEditForm($fiche);
//        $deleteForm = $this->createDeleteForm($id);

        return array(
            'fiche'      => $fiche,
            'form'   => $editForm->createView(),
//            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Creates a form to edit a Customer entity.
     *
     * @param Customer $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Fiche $entity)
    {
        $form = $this->createForm(new FicheEditType(), $entity, array(
            'action' => $this->generateUrl('projet_app_fiche_update', array('id' => $entity->getId())),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('ProjetAppBundle:Fiche')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Fiche entity.');

        }
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        $address = urlencode($entity->getAddress());
        $zipcode = $entity->getZipcode();
        $city = $entity->getCity();

        //Récuperation de la longitude et de la latitude en fonction de l'adresse et du code postal
        $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".$address."".$zipcode."&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
        $json = json_decode($coordpolaire);
        if ($json->{'status'} == "ZERO_RESULTS") {
            $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . $zipcode . "&region=fr&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
            $json = json_decode($coordpolaire);
            if ($json->{'status'} == "OK") {
                $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            } else {
                $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=14000&region=fr&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
                $json = json_decode($coordpolaire);
                $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            }
        } else {
            $entity->setLatitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
            $entity->setLongitude($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
        }
        $em->flush();
        return $this->redirect($this->generateUrl('projet_app_fiche_new'));
    }

    /**
     * Deletes a Fiche entity.
     *
     * @Method("DELETE")
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('ProjetAppBundle:Fiche')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Fiche entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('projet_app_fiche_liste'));
    }
}
