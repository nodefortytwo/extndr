var map;
var marker;
$(document).ready(function() {
    var mapOptions = {
        center : new google.maps.LatLng(51.500152, -0.126236),
        zoom : 10,
        mapTypeId : google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById("map"), mapOptions);

    google.maps.event.addListener(map, 'click', function(event) {
        $('#location_lat').val(event.latLng.Xa);
        $('#location_lng').val(event.latLng.Ya);
        placeMarker(event.latLng);
    });
    
    
    //init wysiwyg
    $('textarea#body').wysihtml5({
        "font-styles" : false, //Font styling, e.g. h1, h2, etc. Default true
        "emphasis" : true, //Italics, bold, etc. Default true
        "lists" : false, //(Un)ordered lists, e.g. Bullets, Numbers. Default true
        "html" : false, //Button which allows you to edit the generated HTML. Default false
        "link" : true, //Button to insert a link. Default true
        "image" : false, //Button to insert an image. Default true,
        "color" : false //Button to change color of font
    });
})

function placeMarker(location) {
  marker = new google.maps.Marker({
      position: location,
      map: map
  });

  map.setCenter(location);
}