<?php

    // REQUIRES:
    require('./model.php');

    // ===============================================================================
    // returns the data as a json
    function retJSON($data) {
        header("Content-type: Application/json");
        print json_encode($data);
        exit;
    }
    // ===============================================================================

    // ===============================================================================
    // store the server's request method into a variable called $path
    $method = strtolower($_SERVER["REQUEST_METHOD"]);
    if(isset($_SERVER["PATH_INFO"])) {
        $path = $_SERVER["PATH_INFO"];
    } else {
        $path = "";
    }
    // ===============================================================================

    // ===============================================================================
    // split the path variable and see if it passes a few basic checks
    $splitPath = explode("/", $path);
    if(count($splitPath) < 2) {
        $return = array("status" => "FAIL", "msg" => "Invalid URL");
        retJSON($return);
    }
    if ($splitPath[1] !== "v1") {
        $return = array("status" => "FAIL", "msg" => "Invalid URL or Version");
        retJSON($return);
    }
    // ===============================================================================

    // ===============================================================================
    // if there is json data, get it
    $jsonData = array();
    try {
        $rawData = file_get_contents("php://input");
        $jsonData = json_decode($rawData, true);
        if($rawData !== "" && $jsonData == NULL) {
            $return = array("status" => "FAIL", "msg" => "invalid json");
            retJSON($return);
        }
    } catch(Exception $e) {
        
    };
    // ===============================================================================

    // ===============================================================================
    // generate a new random token
    function genToken() {
        $token = "";
        $chars = str_split("abcdefghijklmnopqrstuvwxyz0123456789");
        for($i = 0; $i < 30; $i ++) {
            $c = array_rand($chars);
            $token.=$chars[$c];
        }
        return $token;

    }
    // ===============================================================================

    // ===============================================================================
    // rest api logic
    
    // POST - checking if the entered user exists in the database & if yes returns token
    // url = rest.php/v1/user
    if ($method === "post" && count($splitPath) === 4 && $splitPath[2] === "user") {
        if(!isset($jsonData['user']) || !isset($jsonData['password'])) {
            retJSON(array("status" => "FAIL", "msg" => "no user is present", "token" => ""));
        }
        if(verifyUser($jsonData['user'], $jsonData['password'])) {
            $userToken = genToken();
            if(!storeToken($jsonData['user'], $userToken)) {
                retJSON(array("status" => "FAIL", "msg" => "did not store token"));
            }
            retJSON(array("status" => "OK", "msg" => "success", "token" => $userToken));
        }
        retJSON(array("status" => "FAIL", "msg" => "incorrect password for user", "token" => ""));
    }

    // GET - gets the list of items we are tracking & their key
    // url = rest.php/v1/items
    if ($method === "get" && count($splitPath) == 3 && $splitPath[2] === "items") {
        $itemsList = getItemsList();
        retJSON(array("status" => "OK", "msg" => "retrieved items", "items" => $itemsList));
    }

    // GET - gets the list of items that the user has consumed
    // url = rest.php/v1/items/token
    if ($method === "get" && count($splitPath) == 4 && $splitPath[2] === "items") {
        $userKey = getUserKey($splitPath[3]); // gets the user's key from the provided token
        if($userKey == -1) {
            retJSON(array("status" => "AUTH_FAIL", "msg" => "failed to identify user", "items" => $userKey));
        }
        $userItems = getUserItems($userKey, 20);
        retJSON(array("status" => "OK", "msg" => "retrieved items", "items" => $userItems));
    }

    // GET - gets a summary of the items
    // url = rest.php/v1/itemsSummary/token
    if ($method === "get" && count($splitPath) == 4 && $splitPath[2] === "itemsSummary") {
        $userKey = getUserKey($splitPath[3]);
        if($userKey == -1) {
            retJSON(array("status" => "AUTH_FAIL", "msg" => "failed to identify user", "items" => $userKey));
        }
        $itemsSummary = getSummary($userKey);
        retJSON(array("status" => "OK", "msg" => "retrieved items", "items" => $itemsSummary));
    }

    // POST - updates the items consumed
    // url = rest.php/v1/items
    if ($method === "post" && count($splitPath) == 3 && $splitPath[2] === "items") {
        if(!isset($jsonData['token']) || !isset($jsonData['itemFK'])) {
            retJSON(array("status" => "FAIL", "msg" => "no json data is present"));
        }
        $userKey = getUserKey($jsonData['token']);
        if($userKey == -1) {
            retJSON(array("status" => "AUTH_FAIL", "msg" => "failed to identify user"));
        }
        if(!validItemKey($jsonData['itemFK'])) {
            retJSON(array("status" => "FAIL", "msg" => "failed to identify item"));
        }
        if(updateItem($userKey, $jsonData['itemFK'])) {
            retJSON(array("status" => "OK", "msg" => "item succesfully updated!"));
        }
        retJSON(array("status" => "FAIL", "msg" => "failed to update item"));
    }

    retJSON(array("status" => "FAIL", "msg" => "did not recognize request"));
?>
