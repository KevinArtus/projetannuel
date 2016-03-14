<?php

namespace Projet\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Projet\AppBundle\Entity\Fiche;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Ivory\GoogleMapBundle\Entity\Marker;
use Ivory\GoogleMapBundle\Entity\Map;
use Ivory\GoogleMap\Overlays\Animation;
use Ivory\GoogleMap\Overlays\InfoWindow;
use Ivory\GoogleMap\Events\MouseEvent;
use Ivory\GoogleMap\Services\Directions\Directions;
use Widop\HttpAdapter\CurlHttpAdapter;
use Ivory\GoogleMap\Overlays\Polygon;

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
        //RECUPERATION DES FICHES QUI SONT ACTIVES
        $em = $this->getDoctrine()->getManager();
        $fiches = $em->getRepository('ProjetAppBundle:Fiche')->findByStatus(1);

        //CREATION DE LA CARTE
        $map = $this->get('ivory_google_map.map');
        $map->setCenter(49.1755575, -0.3483401, true);
        $map->setMapOption('zoom', 13);

        //CREATION D'UN POLYGONE
        $polygon = $this->drawPolygon();
        foreach($fiches as $fiche) {
            $polygon->addCoordinate($fiche->getLatitude(),$fiche->getLongitude(),true);
            //$map->addPolygon($polygon);
        }

        for ($i = 0; $i < count($fiches); $i++) {
            $directions = new Directions(new CurlHttpAdapter());
            $depart = $fiches[$i]->addressString();

            //CREATION D'UNE FENETRE DE DETAILS DES INFORMATIONS
            $infoWindow = $this->InfoWindowMarker($fiches[$i]);

            //CREATION D'UN MARRKER POUR CHACUNE DES FICHES
            $marker = $this->createMarker($fiches[$i]);
            $marker->setInfoWindow($infoWindow);

            for ($j = 0; $j < count($fiches); $j++) {
                $arrivee = $fiches[$j]->addressString();
                if ($depart != $arrivee) {
                    $response = $directions->route($depart, $arrivee);
                    $routes = $response->getRoutes();

                    //BOUCLE QUI PARCOURS LA LISTE DES ROUTES TROUVEES
                    foreach ($routes as $route) {

                        //AJOUTER UN TRAIT SUR LA CARTE POUR LES TRAJETS
                        $polyline = $route->getOverviewPolyline();
                        $polyline->getValue();
                        $couleurs = array('#1abc9c', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50', '#f39c12', '#d35400', '#c0392b', '#7f8c8d');
                        $nbrcouleurs = count($couleurs) - 1;
                        $rand = rand(0, $nbrcouleurs);
                        $lacouleur = $couleurs[$rand];
                        $polyline->setOption('strokeColor',$lacouleur );
                        unset($couleurs[array_search($lacouleur, $couleurs)]);
                        $map->addEncodedPolyline($polyline);

                        $legs = $route->getLegs();
                        foreach ($legs as $leg) {

                            //MISE A JOUR DE LA FENETRE D'INFORMATION DES MARKERS
                            $string = 'Trajet en direction de : '.$fiches[$j]->getLibelle().'('.$leg->getEndAddress().')
                            , DISTANCE: '.$leg->getDistance()->getText().' DUREE : '.$leg->getDuration()->getText().'<br>';
                            $marker->getInfoWindow()->setContent($marker->getInfoWindow()->getContent().$string);

//                            var_dump("DEPART: " .$leg->getStartAddress().", ARRIVEE: ".$leg->getEndAddress().", DISTANCE: ".$leg->getDistance()->getText()."<br>");
                            $steps = $leg->getSteps();
                            foreach ($steps as $step) {
        //                        var_dump("STEP");
        //                        var_dump($step->getInstructions());
                                //var_dump($step->getStartLocation());
//                                $marker = new Marker();
//                                $marker2 = new Marker();
//                                $marker->setPosition($step->getStartLocation()->getLatitude(),$step->getStartLocation()->getLongitude(),true);
//                                array_push ($tabMarker,$marker);
//                                $marker2->setPosition($step->getEndLocation()->getLatitude(),$step->getEndLocation()->getLongitude(),true);
                                //$marker->setAnimation('bounce');
                               // $marker2->setAnimation('bounce');
                                //$map->addMarker($marker);
                                //$map->addMarker($marker2);
                            }
                        }
                    }
                }
            }
            $map->addMarker($marker);
        }
        return $this->render(
            'ProjetAppBundle:Map:index.html.twig', array('map' => $map)
        );
    }

    public function InfoWindowMarker($fiche) {
        $infoWindow = new InfoWindow();
        $infoWindow->setPrefixJavascriptVariable('info_window_');
        $infoWindow->setPosition($fiche->getLatitude(),$fiche->getLongitude(), true);
        $infoWindow->setContent('<h1>'.$fiche->getLibelle().'</h1><br><address>'.$fiche->addressString().'</address><br>');
        $infoWindow->setPixelOffset(1.1, 2.1, 'px', 'pt');
        $infoWindow->setOpen(false);
        $infoWindow->setAutoOpen(true);
        $infoWindow->setOpenEvent('mouseover');
        $infoWindow->setAutoClose(true);
        $infoWindow->setOption('disableAutoPan', true);
        $infoWindow->setOptions(array(
            'disableAutoPan' => true,
            'zIndex'         => 10,
        ));
        return $infoWindow;
    }

    public function createMarker($fiche) {
        $marker = new Marker();
        $marker->setPosition($fiche->getLatitude(),$fiche->getLongitude(),true);
        $marker->setAnimation(Animation::DROP);
        return $marker;
    }

    public function drawPolygon() {
        $polygon = new Polygon();
        $polygon->setOption('fillColor', '#000000');
        $polygon->setOption('fillOpacity', 0.5);
        $polygon->setOptions(array(
            'fillColor'   => '#000000',
            'fillOpacity' => 0.5,
        ));
        return $polygon;
    }
}