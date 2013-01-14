<?php
/* Simple sample USSD registration application
 * USSD gateway that is being used is Africa's Talking USSD gateway
 */

// Print the response as plain text so that the gateway can read it
header('Content-type: text/plain');

/* local db configuration */
$dsn = 'mysql:dbname=dbname;host=127.0.0.1;'; //database name
$user = 'your user'; // your mysql user 
$password = 'your password'; // your mysql password

//  Create a PDO instance that will allow you to access your database
try {
    $dbh = new PDO($dsn, $user, $password);
}
catch(PDOException $e) {
    //var_dump($e);
    echo("PDO error occurred");
}
catch(Exception $e) {
    //var_dump($e);
    echo("Error occurred");
}

// Get the parameters provided by Africa's Talking USSD gateway
$phone = $_GET['phoneNumber'];
$session_id = $_GET['sessionId'];
$service_code = $_GET['serviceCode'];
$ussd_string= $_GET['text'];

//set default level to zero
$level = 0;

/* Split text input based on asteriks(*)
 * Africa's talking appends asteriks for after every menu level or input
 * One needs to split the response from Africa's Talking in order to determine
 * the menu level and input for each level
 * */
$ussd_string_exploded = explode ("*",$ussd_string);

// Get menu level from ussd_string reply
$level = count($ussd_string_exploded);

if($level == 1 or $level == 0){
    
    display_menu(); // show the home/first menu
}

if ($level > 1)
{

    if ($ussd_string_exploded[0] == "1")
    {
        // If user selected 1 send them to the registration menu
        register($ussd_string_exploded,$phone, $dbh);
    }

  else if ($ussd_string_exploded[0] == "2"){
        //If user selected 2, send them to the about menu
        about($ussd_string_exploded);
    }
}

/* The ussd_proceed function appends CON to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session is till in session or should still continue
 * Use this when you want the application USSD session to continue
*/
function ussd_proceed($ussd_text){
    echo "CON $ussd_text";
}

/* This ussd_stop function appends END to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session should end.
 * Use this when you to want the application session to terminate/end the application
*/
function ussd_stop($ussd_text){
    echo "END $ussd_text";
}

//This is the home menu function
function display_menu()
{
    $ussd_text =    "1. Register \n 2. About \n"; // add \n so that the menu has new lines
    ussd_proceed($ussd_text);
}


// Function that hanldles About menu
function about($ussd_text)
{
    $ussd_text =    "This is a sample registration application";
    ussd_stop($ussd_text);
}

// Function that handles Registration menu
function register($details,$phone, $dbh){
    if(count($details) == 2)
    {
        
        $ussd_text = "Please enter your Full Name and Email, each seperated by commas:";
        ussd_proceed($ussd_text); // ask user to enter registration details
    }
    if(count($details)== 3)
    {
        if (empty($details[1])){
                $ussd_text = "Sorry we do not accept blank values";
                ussd_proceed($ussd_text);
        } else {
        $input = explode(",",$details[1]);//store input values in an array
        $full_name = $input[0];//store full name
        $email = $input[1];//store email
        $phone_number =$phone;//store phone number 

        // build sql statement
        $sth = $dbh->prepare("INSERT INTO customer (full_name, email, phone) VALUES('$full_name','$email','$phone_number')");
        //execute insert query   
        $sth->execute();
        if($sth->errorCode() == 0) {
            $ussd_text = $full_name." your registration was successful. Your email is ".$email." and phone number is ".$phone_number;
            ussd_proceed($ussd_text);
        } else {
            $errors = $sth->errorInfo();
        }
    }
}
}
# close the pdo connection  
$dbh = null;
?>