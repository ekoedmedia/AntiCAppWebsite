<?php
/* This file returns an image with the requested image name from 
 * ../doseAdjustCharts/ to the user
 */

function sendImage($name) {
	$fullName = __DIR__."/../doseAdjustCharts/" + $name;

	if (file_exists($fullName)) {
		HttpResponse::setBufferSize(10000);
		HttpResponse::setThrottleDelay(0.1);
		HttpResponse::setContentDisposition($name, True);
		HttpResponse::setContentType("image/jpeg");
		HttpResponse::setFile($fullName);
		HttpResponse::send();
//		http_send_content_disposition($name, True);
//		http_send_content_type("image/jpeg");
//		http_throttle(0.1, 10000);
//		http_send_file($fullName);
	}
}
