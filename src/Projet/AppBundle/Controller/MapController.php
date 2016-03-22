<?php

namespace Projet\AppBundle\Controller;

use Ivory\GoogleMap\Services\Geocoding\Geocoder;
use Ivory\GoogleMap\Services\Geocoding\GeocoderRequest;
use Ivory\GoogleMapBundle\Tests\Fixtures\Model\Services\DistanceMatrix\DistanceMatrix;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Projet\AppBundle\Entity\Fiche;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Ivory\GoogleMapBundle\Entity\Marker;
use Ivory\GoogleMapBundle\Entity\Map;
use Ivory\GoogleMap\MapTypeId;
use Ivory\GoogleMap\Overlays\Animation;
use Ivory\GoogleMap\Overlays\InfoWindow;
use Ivory\GoogleMap\Events\MouseEvent;
use Ivory\GoogleMap\Services\Directions\Directions;
use Ivory\GoogleMap\Services\Directions\DirectionsRequest;
use Widop\HttpAdapter\CurlHttpAdapter;
use Ivory\GoogleMap\Overlays\Polygon;
use Ivory\GoogleMap\Services\DistanceMatrix\DistanceMatrixRequest;
use Ivory\GoogleMap\Services\Base\TravelMode;
use Ivory\GoogleMap\Services\Base\UnitSystem;
use Projet\AppBundle\Entity\FicheMilieu;
use Ivory\GoogleMap\Events\Event;

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
        $fiches = $em->getRepository('ProjetAppBundle:Fiche')->findBy(array('status' => '1','user' => $this->getUser()));

        //CREATION DE LA CARTE
//        $map = $this->get('ivory_google_map.map');
        $map = new Map();
        $map->setPrefixJavascriptVariable('map_');
        $map->setHtmlContainerId('map_canvas');
        $map->setAsync(false);
        $map->setAutoZoom(true);
        $map->setCenter(-0.3483401, 49.1755575, true);
        $map->setMapOption('zoom', 13);
        $map->setBound(-2.1, -3.9, 2.6, 1.4, true, true);
        $map->setMapOption('mapTypeId', 'roadmap');
        $map->setMapOption('disableDefaultUI', false);
        $map->setMapOption('disableDoubleClickZoom', true);
        $map->setStylesheetOption('width', '1200px');
        $map->setStylesheetOption('height', '700px');
        $map->setLanguage('fr');



        //CREATION D'UN POLYGONE
        $polygon = $this->drawPolygon();
        $tab = array();

        foreach($fiches as $fiche) {
//            $polygon->addCoordinate($fiche->getLatitude(),$fiche->getLongitude(),true);
//            $map->addPolygon($polygon);
        }

        for ($i = 0; $i < count($fiches); $i++) {
            $directions = new Directions(new CurlHttpAdapter());
            $depart = $fiches[$i]->addressString();

            //CREATION D'UNE FENETRE DE DETAILS DES INFORMATIONS
            $infoWindow = $this->InfoWindowMarker($fiches[$i]);
            $infoWindow->setContent('<h1>'.$fiches[$i]->getLibelle().'</h1><br><address>'.$fiches[$i]->addressString().'</address><br>');

            //CREATION D'UN MARRKER POUR CHACUNE DES FICHES
            $marker = $this->createMarker($fiches[$i]);
            $marker->setJavascriptVariable('marker_fiche_'.$fiches[$i]->getId());

            //AJOUTER UNE FENETRE DE DETAILS POUR LES MARQUEURS
            $marker->setInfoWindow($infoWindow);

            for ($j = 0; $j < count($fiches); $j++) {
                $arrivee = $fiches[$j]->addressString();
                if ($depart != $arrivee) {

                    //RETROUVER LE POINT DU MILIEU ENTRE DEUX ADRESSES
                    $geotools = new \League\Geotools\Geotools();
                    $coordA   = new \League\Geotools\Coordinate\Coordinate([$fiches[$i]->getLongitude(), $fiches[$i]->getLatitude()]);
                    $coordB   = new \League\Geotools\Coordinate\Coordinate([$fiches[$j]->getLongitude(), $fiches[$j]->getLatitude()]);
                    $vertex    =  $geotools->vertex()->setFrom($coordA)->setTo($coordB);
                    $middlePoint = $vertex->middle();

                    //PERSISTE LES MIDPOINTS DANS LA BASE DE DONNEES
                    $ficheMilieu = new FicheMilieu();
                    $ficheMilieu->setLibelle('Milieu entre : '.$fiches[$i]->getLibelle().' et '.$fiches[$j]->getLibelle());
                    $ficheMilieu->setLongitude($middlePoint->getLongitude());
                    $ficheMilieu->setLatitude($middlePoint->getLatitude());
                    $ficheMilieu->setFicheA($fiches[$i]);
                    $ficheMilieu->setFicheB($fiches[$j]);
                    $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=".$ficheMilieu->getLongitude().",".
                        $ficheMilieu->getLatitude()."&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
                    $json = json_decode($coordpolaire);
//                    echo '<pre>';
//                    var_dump($json->{'results'}[0]->{'formatted_address'});
//                    echo '</pre>';
                    $address = $json->{'results'}[0]->{'formatted_address'};
                    $res = explode(",",$address);
                    $ficheMilieu->setAddress($res[0]);
                    $res2 = explode(" ",$res[1]);
                    $ficheMilieu->setZipcode($res2[1]);
                    $ficheMilieu->setCity($res2[2]);
                    $em = $this->getDoctrine()->getManager();
                    $findFicheMilieu = $em->getRepository('ProjetAppBundle:FicheMilieu')->findByAddress($ficheMilieu->getAddress());
                    if(empty($findFicheMilieu)) {
                        $em->persist($ficheMilieu);
                        $em->flush();
                    }

                    $response = $directions->route($depart, $arrivee);
                    $routes = $response->getRoutes();
                    //BOUCLE QUI PARCOURT LA LISTE DES ROUTES TROUVEES
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
                            $count = $leg->getDistance()->getValue();
//                            var_dump($count);
                            $steps = $leg->getSteps();
                            foreach ($steps as $step) {
                                $midPoint = "";
//                                if($step->getDistance()->getValue() == $count) {
//                                    var_dump("OKOKOKOKOK");
//                                } else {
//                                    var_dump($step->getDistance()->getValue());
//                                }


        //                        var_dump("STEP");
        //                        var_dump($step->getInstructions());
//                                var_dump($step->getStartLocation());
                                $marker3 = new Marker();
//                                $marker2 = new Marker();
                                $marker3->setPosition($step->getStartLocation()->getLatitude(),$step->getStartLocation()->getLongitude(),true);
//                                array_push ($tabMarker,$marker);
//                                $marker2->setPosition($step->getEndLocation()->getLatitude(),$step->getEndLocation()->getLongitude(),true);
                                //$marker->setAnimation('bounce');
                               // $marker2->setAnimation('bounce');
                                //$map->addMarker($marker3);
                                //$map->addMarker($marker2);

                            }
                        }
                    }
                }
            }
//            foreach($tab as $value){
//                $polygon->addCoordinate($value->getLongitude(),$value->getLatitude(),true);
//            }
//            $map->addPolygon($polygon);

            $map->addMarker($marker);
        }

        $tabMarker = $this->displayMiddlePoint($fiches);
        foreach($tabMarker as $markers) {
            $map->addMarker($markers);
        }

//        $event = new Event();
//        $event->setInstance($map->getJavascriptVariable());
//        $event->setEventName("click");
//        $handle = 'function(e){
//            var pos = e.latLng;
//            var direction = new google.maps.DirectionsService();
//            var directionsDisplay = new google.maps.DirectionsRenderer;
//
//            var contentString = "Marker";
//                oInfo = new google.maps.InfoWindow();
//
//            directionsDisplay.setMap(this);
//            directionsDisplay.setPanel(document.getElementById("directionsPanel"));
//
//            var marker = new google.maps.Marker({
//                position: pos,
//                animation: google.maps.Animation.DROP,
//                title: "Uluru (Ayers Rock)",
//                map: this
//            });
//            google.maps.event.addListener(marker, \'click\', function() {
//                  oInfo.setContent( "lol");
//                oInfo.open(this, marker);
//            });
//
//             var request = {
//                origin:marker.position,
//                destination:"46 rue jules ferry 14000 caen",
//                travelMode: google.maps.TravelMode.DRIVING
//            };
//
//            direction.route(request, function(response, status) {
//                if (status === google.maps.DirectionsStatus.OK) {
//                    directionsDisplay.setDirections(response);
//                } else {
//                    window.alert(\'Directions request failed due to \' + status);
//                }
//            });
//
//            console.log(marker_fiche_2);
//        }';
//        $event->setHandle($handle);
//
//        $map->getEventManager()->addDomEvent($event);
        return $this->render(
            'ProjetAppBundle:Map:index.html.twig', array('map' => $map, 'fiches' => $fiches)
        );
    }

    /**
     * Crée une fenêtre d'information pour un marker
     * @param $fiche, la fiche pour laquelle il créer une fenêtre d'informations
     * @return InfoWindow
     * @throws \Ivory\GoogleMap\Exception\AssetException
     * @throws \Ivory\GoogleMap\Exception\OverlayException
     */
    public function InfoWindowMarker($fiche) {
        $infoWindow = new InfoWindow();
        $infoWindow->setPrefixJavascriptVariable('info_window_');
        $infoWindow->setPosition($fiche->getLatitude(),$fiche->getLongitude(), true);
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

    /**
     * Crée un objet Market
     * @param $fiche, la fiche pour laquelle il faut créer un marker
     * @return Marker
     * @throws \Ivory\GoogleMap\Exception\OverlayException
     */
    public function createMarker($fiche) {
        $marker = new Marker();
        $marker->setPosition($fiche->getLatitude(),$fiche->getLongitude(),true);
        $marker->setAnimation(Animation::DROP);
        return $marker;
    }

    /**
     * Crée un objet Polygon
     * @return Polygon
     * @throws \Ivory\GoogleMap\Exception\AssetException
     */
    public function drawPolygon() {
        $polygon = new Polygon();
        $polygon->setOption('fillColor', '#000000');
        $polygon->setOption('fillOpacity', 0.0);
        return $polygon;
    }

    public function displayMiddlePoint($fiches) {
        $em = $this->getDoctrine()->getManager();
        $fichesMilieux = $em->getRepository('ProjetAppBundle:FicheMilieu')->findAll();
        $tabMarker = array();
        foreach($fichesMilieux as $ficheMilieu) {
            $marker = $this->createMarker($ficheMilieu);
            $marker->setPrefixJavascriptVariable('marker_fiche_milieu_'.$ficheMilieu->getId());
            $marker->setPosition($ficheMilieu->getLongitude(),$ficheMilieu->getLatitude(),false);

            $infoWindow = $this->InfoWindowMarker($ficheMilieu);
            $infoWindow->setContent('<h1>Milieu entre : '.$ficheMilieu->getFicheA()->getLibelle().' et '.$ficheMilieu->getFicheB()->getLibelle().'</h1>');
            $marker->setInfoWindow($infoWindow);
            $marker = $this->drawRoute($ficheMilieu,$marker,$fiches);
//        echo '<pre>';
//        var_dump($polylines);
//        echo'</pre>';
//            $marker->setInfoWindow($polylines);
            //$map->addEncodedPolyline($polylines[1]);
            $tabMarker[] = $marker;
        }
        return $tabMarker;

    }

    /**
     * Trouve un trajet pour un "point du milieu" avec toutes les fiches
     * @param $ficheMilieu
     * @param $infoWindow
     * @param $fiches
     * @return array
     * @throws \Ivory\GoogleMap\Exception\DirectionsException
     */
    public function drawRoute($ficheMilieu, $marker, $fiches) {

        //var_dump("LONGITUDE : ".$middlePoint->getLongitude()." LATITUDE : ".$middlePoint->getLatitude());
        $Matab = array();

        foreach($fiches as $fiche) {

            $request = new DirectionsRequest();
            $request->setOrigin($ficheMilieu->addressString(), true);
            $request->setDestination($fiche->addressString(), true);
            $request->setAvoidHighways(true);
            $request->setAvoidTolls(true);
            $request->setTravelMode(TravelMode::DRIVING);
            $request->setUnitSystem(UnitSystem::METRIC);
            $directions = new Directions(new CurlHttpAdapter());
            $response = $directions->route($request);

            $routes = $response->getRoutes();
            foreach ($routes as $route) {
                //AJOUTER UN TRAIT SUR LA CARTE POUR LES TRAJETS
                $polyline = $route->getOverviewPolyline();
                $polyline->getValue();
                $couleurs = array('#1abc9c', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50', '#f39c12', '#d35400', '#c0392b', '#7f8c8d');
                $nbrcouleurs = count($couleurs) - 1;
                $rand = rand(0, $nbrcouleurs);
                $lacouleur = $couleurs[$rand];
                $polyline->setOption('strokeColor', $lacouleur);
                unset($couleurs[array_search($lacouleur, $couleurs)]);
                $tabInfoWindows = array();

                $legs = $route->getLegs();
                foreach ($legs as $leg) {
                    $string = 'Trajet en direction de : ' . $fiche->getLibelle() . '(' . $leg->getEndAddress() . ')
                            , DISTANCE: ' . $leg->getDistance()->getText() . ' DUREE : ' . $leg->getDuration()->getText() . '<br>';
                    $marker->getInfoWindow()->setContent($marker->getInfoWindow()->getContent() . $string);
                }
//                $Matab[] = $infoWindow;
//                $Matab[] = $polyline;
            }
        }
        return $marker;
    }

    public function ajaxAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
//            $session = $request->getSession();
//            $latitude = json_decode( $session->get('lat') );
//            $longitude = json_decode( $session->get('lng') );

            $latitude = $request->request->get('lat');
            $longitude = $request->request->get('lng');
            $coordpolaire = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=".$latitude.",".
                $longitude."&key=AIzaSyDePZt3uyPPCISJtyM5nvkmL_s5YxjcqBo");
            $json = json_decode($coordpolaire);
//                    echo '<pre>';
//                    var_dump($json->{'results'}[0]->{'formatted_address'});
//                    echo '</pre>';
            $address = $json->{'results'}[0]->{'formatted_address'};

//            $latitude = json_decode($request->request->get('lat'));
//            $longitude = json_decode($request->request->get('lng'));

            $em = $this->getDoctrine()->getManager();
            $fiches = $em->getRepository('ProjetAppBundle:Fiche')->findBy(array('status' => '1','user' => $this->getUser()));
            $res = array();
            $lol="";
            foreach($fiches as $fiche) {
                $directionRequest = new DirectionsRequest();
                $directionRequest->setOrigin($address, false);
//                $directionRequest->setOrigin("46 rue jules ferry 14120 mondeville", true);
                $directionRequest->setDestination($fiche->addressString(), true);
                $directionRequest->setAvoidHighways(true);
                $directionRequest->setAvoidTolls(true);
                $directionRequest->setTravelMode(TravelMode::DRIVING);
                $directionRequest->setUnitSystem(UnitSystem::METRIC);
                $directions = new Directions(new CurlHttpAdapter());
                $responseDirection = $directions->route($directionRequest);

                $routes = $responseDirection->getRoutes();

                foreach ($routes as $route) {
                    $res = $route;
                }
            }
//            $encoders = new JsonEncoder();
//            $normalizers = array(new GetSetMethodNormalizer());
//            $serializer = new Serializer($normalizers, $encoders);
//            $json = $serializer->serialize($res, 'json');
            $response = new Response($json);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
//            return new JsonResponse($lol);
        }
    }
}