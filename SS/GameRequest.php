<?php
include "GameState.php";

//Get the parameters of the request:
$action = $_REQUEST["action"];
$details = $_REQUEST["details"];
$comID = $_REQUEST["comID"];

try {
   //echo "FAILURE|Foo";
    
   $dbFile = "GameInfo.txt";
   $gameState = new GameState($dbFile);
   $gameState->ProcessAction($comID, $action, $details);
   echo $gameState->ConstructOutputResponse($comID);

} catch(Exception $e) {
   echo "FAILURE|" . $e->getMessage();
}

