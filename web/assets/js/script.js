$(document).ready(function () {

    function initMap() {

    var map = new google.maps.Map(document.getElementById("map_test"),  {
        center: {lat: -34.397, lng: 150.644},
        zoom: 8
    });
    map.addListener('click',function(e) {
        console.log("toto");
        placeMarkerAndPanTo(e.latLng, map);
    });
    }
});

function placeMarkerAndPanTo(latLng, map) {
    var marker = new google.maps.Marker({
        position: latLng,
        map: map
    });
    map.panTo(latLng);
}