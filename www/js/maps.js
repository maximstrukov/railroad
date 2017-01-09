  var map;
  var rg;
  var cg;
  var marker;
  var records, found, current, logo, mi, FirmDescription;
  var lat1, lng1;
  //var Markers = new Array;


  function initApp() {
    $("#previous").attr("disabled","disabled");
    $("#next").attr("disabled","disabled");
    if (GBrowserIsCompatible()) {
      map = new GMap2(document.getElementById('map'));
      map.setCenter(new GLatLng(50, 36.24), 12);
      map.addControl(new GSmallMapControl());
      map.addControl(new GMapTypeControl());

	  cg = new GClientGeocoder();

	getfirms();

      //Добавление маркера
      
      /*var opts = {
        "title": "OK",
        "draggable": true
      }
      marker = new GMarker(new GLatLng(49.996268, 36.232799), opts);
      map.addOverlay(marker);

      marker = new GMarker(new GLatLng(49.94932, 36.266306), opts);
      map.addOverlay(marker);*/

      
    }else{
      alert('Простите, но ваш браузер не совместим с Google Maps');
    }
  }

function getfirms() {
    $.ajax({
	type: "POST",
	url: "getfirms.php",
	datatype: "text",
	data: "firms=1",
	success: function(text) {
            //alert(text);
            records = text.split("***");
            for (mi = 0; mi < records.length-1; mi++) {
                fields = records[mi].split("|||");
				searchText = fields[2] + " " + fields[3] + " " + fields[4] + " " + fields[5];
				FirmName = fields[1];
				//alert(FirmName);
				FirmDescription = fields[6];
				//alert(FirmName + ' ' + FirmDescription);
				cg.getLocations(searchText, CGCallbackMarkers);
				var opts = {
    				"title": FirmName,
        			"draggable": true
				}
				alert(lat1);
      			marker = new GMarker(new GLatLng(lat1, lng1), opts);
      			// Устанавливаем обработчки события нажатия на маркер   
    			GEvent.addListener(marker, "click", function() {
        		// Открытие информационного окна с текстом   
        		marker.openInfoWindowHtml(FirmDescription);   
    			});  
      			map.addOverlay(marker);
            }
        }
    });
}


  
  function CGCallbackMarkers(responce) {
    if (responce.Status.code != 200) {
      alert(responce.Status.code);
      return;
    }
    lat1 = responce.Placemark[0].Point.coordinates[1];
    lng1 = responce.Placemark[0].Point.coordinates[0];
	//if (mi == 0) map.setCenter(new GLatLng(lat, lng));
	//alert(lat);

      //Добавление маркера
	//alert(FirmDescription);

  }
  
  
if ('undefined' == typeof String.prototype.trim) {
  String.prototype.trim = function() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
  }
}

  function RGSuccess(placemark) {
    
    if (!('AddressDetails' in placemark)||placemark.AddressDetails.Accuracy == 0) {
      alert('Я об этом месте ничего не знаю.');
      return;
    }
    var address = placemark.AddressDetails;
    if (address.Accuracy > 2) {
      if ('SubAdministrativeArea' in address.Country.AdministrativeArea)
        var adminArea = address.Country.AdministrativeArea.SubAdministrativeArea;
      else
        var adminArea = address.Country.AdministrativeArea;
    }
    switch(address.Accuracy) {
      case 8:
        street = adminArea.Locality.Thoroughfare.ThoroughfareName.split(",");
	$("#house-id").val(street[0]);
      case 7:
      case 6:
        street = adminArea.Locality.Thoroughfare.ThoroughfareName.split(",");
	$("#house-id").val(street[0]);
	$("#firm-id").val(FirmName);
	$("#street-id").val(street[1].trim());
      case 5:
      case 4:
        $("#city-id").val(adminArea.Locality.LocalityName);
      case 3:
      case 2:
        $("#state-id").val(address.Country.AdministrativeArea.AdministrativeAreaName);
      case 1:
        $("#country-id").val(address.Country.CountryNameCode);
    }
  }
  
  function CGCallback(responce) {
    if (responce.Status.code != 200) {
      alert(responce.Status.code);
      return;
    }
    var lat = responce.Placemark[0].Point.coordinates[1];
    var lng = responce.Placemark[0].Point.coordinates[0];
    document.getElementById('lat-id').value = lat;
    document.getElementById('lng-id').value = lng;
    map.setCenter(new GLatLng(lat, lng));
    marker.setLatLng(new GLatLng(lat, lng));
    RGSuccess(responce.Placemark[0]);
  }
  

  function setLocation(location) {
    if (location == '') {
      alert('Забыли указать место.');
      form.location.focus();
    } else {
      //получение адреса по названию фирмы
    $.ajax({
	type: "POST",
	url: "getadress.php",
	datatype: "text",
	data: "title=" + location,
	success: function(text) {
            if (text !== 'none') {
                records = text.split("***");
                found = records.length - 1;
                current = 0;
                fields = records[current].split("|||");
                searchText = fields[2] + " " + fields[3] + " " + fields[4] + " " + fields[5];
                clearFields();
                FirmName = fields[1];
                if (found > 1) $("#next").removeAttr('disabled');
                else {
    				$("#previous").attr("disabled","disabled");
    				$("#next").attr("disabled","disabled");
                }
                cg.getLocations(searchText, CGCallback);
            }
        }
    });

    }
    return false;
  }
  
  function clearFields() {
    $("#firm-id").val('');
    $("#lat-id").val('');
    $("#lng-id").val('');
    $("#country-id").val('');
    $("#state-id").val('');
    $("#city-id").val('');
    $("#street-id").val('');
    $("#house-id").val('');
  }

  function next() {
      current += 1;
      if (current < found) {
          fields = records[current].split("|||");
          searchText = fields[2] + " " + fields[3] + " " + fields[4] + " " + fields[5];
          clearFields();
          FirmName = fields[1];
          cg.getLocations(searchText, CGCallback);
		  $("#previous").removeAttr('disabled');
          if (current == (found-1))	$("#next").attr("disabled","disabled");
      }
  }

  function previous() {
      current -= 1;
      if (current >= 0) {
          fields = records[current].split("|||");
          searchText = fields[2] + " " + fields[3] + " " + fields[4] + " " + fields[5];
          clearFields();
          FirmName = fields[1];
          cg.getLocations(searchText, CGCallback);
		  $("#next").removeAttr('disabled');
          if (current == 0)	$("#previous").attr("disabled","disabled");
      }
  }