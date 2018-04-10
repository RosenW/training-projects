<?php

/* Template Name: Display data */

?>

<?php get_header();?>

<div>

<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<style>
#map {
        height: 400px;
        width: 100%;
}
.multiselect {
  width: 200px;
}

.selectBox {
  position: relative;
}

.selectBox select {
  width: 100%;
  font-weight: bold;
}

.overSelect {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
}

#checkboxes {
  display: none;
  border: 1px #dadada solid;
}

#checkboxes label {
  display: block;
}

#checkboxes label:hover {
  background-color: #1e90ff;
}
</style>
</head>

<div id="map"></div>

<form style="float: left">
    From:<br>
    <input type="text" id="from" placeholder="Lat, Long">
    <br>
    To:<br>
    <input type="text" id="to" placeholder="Lat, Long">
    <br>
    <button type="button" id="submitBtn">Search</button>
    <button type="button" id="showAllBtn">Show All</button>
    <button type="button" id="hideAllBtn">Hide All</button>
</form>

<form style="float: left">
    Find by name:<br>
    <input type="text" name="name" class="autocomplete" id="findBox">
    <br><br>
</form>

<div id="count"></div>

<script>

$(function() {
    $('#submitBtn').click(submit);
    $('#showAllBtn').click(showAllMarkers);
    $('#hideAllBtn').click(clearAllMarkers);

    fromBox = $('#from').change(changePlaces);
    toBox = $('#to').change(changePlaces);


    $.ui.autocomplete.filter = function(array, term) {
        var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex(term), "i");
        return $.grep(array, function(value) {
            return matcher.test(value.label || value.value || value);
        });
    };

    $(".autocomplete").autocomplete({
        minLength: 2,
        delay: 700,
        source: function(request, response) {
                        let results = $.ui.autocomplete.filter(filteredPlaces, request.term);
                        response(results);
                    }
    });

});

let bulgariaCoords = {
    lat: 42.7,
    lng: 25.4
};
let markerCount = 0;
let map;
let expanded = false;
let markers = [];
let filteredPlaces = [];
let fromBox;
let toBox;
<?php global $wpdb;?>

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: bulgariaCoords,
        zoom: 6
    });

    showAllMarkers();
}

function showAllMarkers(){
<?php
    $result = $wpdb->get_results("SELECT * FROM markers");
    foreach ($result as $print){ ?>
	var marker = new google.maps.Marker({
    	position: {lat: <?php echo $print->lat; ?>,lng: <?php echo $print->lng; ?>},
    	map: map
  });
	filteredPlaces.push("<?php echo preg_replace("/\r?\n/", "\\n", addslashes($print->name)); ?>");
	markers.push(marker);
<?php
    }
?>
}

function clearAllMarkers() {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
    markerCount = 0;
}

function submit() {
    clearAllMarkers();
    $.get("/markers/all").done(function(allMarkers) {
        let nameToSearch = $("#findBox").val();
        if (nameToSearch === "") {
            allMarkers.forEach((element) => {
                filteredPlaces.forEach((place) => {
                    if (element.name == place) {
                        let curMarker = new google.maps.Marker({
                            position: {
                                lat: element.lat,
                                lng: element.lng
                            },
                            map: map
                        });
                        markers.push(curMarker);
                        markerCount++;
                    }
                });
            });
        } else {
            allMarkers.forEach((element) => {
                if (nameToSearch == element.name) {
                    let curMarker = new google.maps.Marker({
                        position: {
                            lat: element.lat,
                            lng: element.lng
                        },
                        map: map
                    });
                    markers.push(curMarker);
                    markerCount++;
                }
            });
        }
        showMarkerCount();
    });
}

function changePlaces() {
    let fromCoords = fromBox.val().split(", ");
    let toCoords = toBox.val().split(", ");
    let selectedValues = [];

    if (fromCoords.length === 2 && toCoords.length === 2 && isNumber(fromCoords[0]) && isNumber(fromCoords[1]) && isNumber(toCoords[0]) && isNumber(toCoords[1])) {
        while (filteredPlaces.length > 0) {
            filteredPlaces.pop();
        }
//<?php
//$results = $wpdb->get_results("SELECT * FROM markers as m where m.lat > 0 and m.lat < 30 and m.lng > 0 and m.lng < 50");
//$results = $wpdb->get_results("SELECT * FROM markers as m where m.lat > 0 and m.lat < 30 and m.lng > 0 and m.lng < 50");
//?>
	
        $.get("/markers/" + fromCoords[0] + "/" + toCoords[0] + "/" + fromCoords[1] + "/" + toCoords[1]).done(function(selectedMarkers) {
            $.get("/relations/all").done(function(relations) {
                $("input:checkbox").each(function() {
                    let $this = $(this);
                    if ($this.is(":checked")) {
                        selectedValues.push($this.attr("id"));
                    }
                });

                let curSelectedMarkers = [];
                selectedMarkers.forEach((marker) => {
                    selectedValues.forEach((val) => {
                        let markerIsOfType = false;
                        let markerAlreadySelected = false;

                        curSelectedMarkers.forEach((curSelectedMarker) => {
                            if (curSelectedMarker == marker.id) {
                                markerAlreadySelected = true;
                            }
                        });

                        relations[marker.id].forEach((markerType) => {
                            if (markerType == val) {
                                markerIsOfType = true;
                            }
                        });
                        if (markerIsOfType && !markerAlreadySelected) {
                            curSelectedMarkers.push(marker.id);
                            filteredPlaces.push(marker.name);
                        }
                    });
                });
            });
        });
    }
}

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}
function showMarkerCount() {
    $("#count").text("Markers on map: " + markerCount);
}

</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWCr6tIiwBUyJvMSaEHkFHtSkRPDH8VmU&callback=initMap"></script>
</div>

<?php get_footer();?>