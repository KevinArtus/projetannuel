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
        $em = $this->getDoctrine()->getManager();

        $map = $this->get('ivory_google_map.map');
        $map->setCenter(49.1755575, -0.3483401, true);
        $map->setMapOption('zoom', 12);

        $fiches = $em->getRepository('ProjetAppBundle:Fiche')->findAll();
        foreach($fiches as $value) {
            $infoWindow = new InfoWindow();
            $infoWindow->setPrefixJavascriptVariable('info_window_');
            $infoWindow->setPosition($value->getLatitude(),$value->getLongitude(), true);
            $infoWindow->setPixelOffset(1.1, 2.1, 'px', 'pt');
            $infoWindow->setContent('<h1>'.$value->getLibelle().'</h1><br><address>'.$value->addressString().'</address>');
            $infoWindow->setOpen(false);
            $infoWindow->setAutoOpen(true);
            $infoWindow->setOpenEvent('mouseover');
            $infoWindow->setAutoClose(true);
            $infoWindow->setOption('disableAutoPan', true);
            $infoWindow->setOptions(array(
                'disableAutoPan' => true,
                'zIndex'         => 10,
            ));

            $marker = new Marker();
            $marker->setPosition($value->getLatitude(),$value->getLongitude(),true);
            $marker->setAnimation(Animation::DROP);
            //$marker->setAnimation('bounce');
            $marker->setInfoWindow($infoWindow);

            $map->addMarker($marker);


        }

        for($i=0;$i<count($fiches);$i++) {
            $directions = new Directions(new CurlHttpAdapter());
            /***if($i+1<count($fiches)){
                $response = $directions->route($fiches[$i]->addressString(), $fiches[$i+1]->addressString());

            } else {
                $response = $directions->route($fiches[$i]->addressString(), $fiches[$i-1]->addressString());

            }*/
            $response = $directions->route($fiches[0]->addressString(), "53 Rue de la Mer 14550 Blainville-sur-Orne");

            $routes = $response->getRoutes();
            foreach ($routes as $route) {
                var_dump($route->getSummary());

                $legs = $route->getLegs();
                $map->addEncodedPolyline($route->getOverviewPolyline());

                foreach ($legs as $leg) {
                    $steps = $leg->getSteps();
//                    var_dump("LEG");
//                    var_dump($leg->getDistance());
                    foreach ($steps as $step) {
//                        var_dump("STEP");
//                        var_dump($step->getInstructions());
                        //var_dump($step->getStartLocation());
                        $marker = new Marker();
                        $marker2 = new Marker();
                        $marker->setPosition($step->getStartLocation()->getLatitude(),$step->getStartLocation()->getLongitude(),true);
                        $marker2->setPosition($step->getEndLocation()->getLatitude(),$step->getEndLocation()->getLongitude(),true);
                        $marker->setAnimation('bounce');
                       // $marker2->setAnimation('bounce');
                        $map->addMarker($marker);
                        $map->addMarker($marker2);


                    }
                }
            }
        }


        return $this->render(
            'ProjetAppBundle:Map:index.html.twig', array('map' => $map)
        );
    }
}