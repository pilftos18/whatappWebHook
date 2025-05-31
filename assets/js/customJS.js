/**
* Template Name: NiceAdmin
* Updated: May 30 2023 with Bootstrap v5.3.0
* Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

function validateVehicleNumber(vehicleNumber) {
  var returnStr = false;
  // Check if the vehicle number is empty
  if (vehicleNumber.trim() === '') {
      return returnStr;
  }
  // Regular expression pattern for vehicle number validation
  // var regex = /^[A-Z]{2}[0-9]{1,2}[A-Z]{1,2}[0-9]{1,4}$/;DL04SCA1585
  
  var regex = /^[A-Z]{2}[0-9]{1,2}[A-Z0-9]{1,3}[0-9]{1,4}$/;
  // Test the vehicle number against the regex pattern
  var isValid = regex.test(vehicleNumber);
  if(isValid === false)
  {
    var BHregex = /^\d{2}BH\d{1,4}[A-Z]{1,2}$/; 
    isValid     = BHregex.test(vehicleNumber);
  }
  return isValid;
}

function validateChassisNumber(Number) {
  // Check if the vehicle number is empty
  if (Number.trim() === '') {
      return false;
  }

  // Regular expression pattern for vehicle number validation
  var regex = /^[A-HJ-NPR-Z0-9]{17}$/i;

  // Test the vehicle number against the regex pattern
  var isValid = regex.test(Number);

  return isValid;
}

function validateChassisLastFiveDigit(chassisNumber) {
  // Check if the vehicle number is empty
  if (chassisNumber.trim() === '') {
      return false;
  }
  var regex = /^[0-9]{5}$/;
  // Test the vehicle number against the regex pattern
  var isValid = regex.test(chassisNumber);

  return isValid;
}