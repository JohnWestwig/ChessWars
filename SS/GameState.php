<?php
//include "ChessPosition.php";

class GameState {
   private $fp = 0;
   private $Player1GUID = "";
   private $Player2GUID = "";
   private $GameStatus = "";
   private $GamePosition = "";
   private $Dirty = FALSE;
      
   public function __construct($filePath) {
      while (!$this->fp = fopen($filePath , 'r+')) {usleep(100000);}
      while (!flock($this->fp, LOCK_EX)) {usleep(1000000);}
      $fileText = fread($this->fp, filesize($filePath));    
      $this->ParseDatabaseFileText($fileText);
   }
    
   public function __destruct() {
      if($this->Dirty) {
         $updatedText = $this->ConstructDatabaseFileText();
         ftruncate($this->fp, 0);
    	   fseek($this->fp, 0);
    	   fwrite($this->fp, $updatedText);
      }
      flock($this->fp, LOCK_UN);
      fclose($this->fp);
   }
   
   private function ParseDatabaseFileText($fileText) {
      $statePieces = explode("|", $fileText);
      $this->Player1GUID = $statePieces[0];
      $this->Player2GUID = $statePieces[1];
      $this->GameStatus = $statePieces[2];
      $this->GamePosition = $statePieces[3];
   }

   private function ConstructDatabaseFileText() {
      //return $this->Player1GUID . "|" . $this->Player2GUID . "|" . $this->GameStatus . "|" . $this->GamePosition;
   }

   public function ConstructOutputResponse($comID) {
      return "SUCCESS|" . $this->GameStatus . "|" . $this->GamePosition;
   }

   //public function IdentifyPlayer($comID) {
   //  if ($comID == $this->WhiteComID) return "W";
   //   if ($comID == $this->BlackComID) return "B";
   //   return "?";
   //}

   
   //********************************************************************************************************
   //Actions:
 
   public function ProcessAction($comID, $actionName, $actionDetails) {  
 
      //Dispatch out to the correct routing:
      switch($actionName) {
         case "STATE":  break;  //No action required.
         case "MOVE":   $this->ProcessMove($comID, $actionDetails);  break;
         //case "JOIN":   $this->ProcessJoin($comID, $actionDetails);  break;
         //case "LEAVE":  $this->ProcessLeave($comID);                 break;
         //case "RESET":  $this->ProcessReset($actionDetails);         break;
         //default:       throw new Exception("Unknown Command.");     break;
      } 
   }

  /* private function ProcessMove($comID, $moveText) {
      $side = $this->IdentifyPlayer($comID);
      $chessPosition = new ChessPosition($this->FEN);
      //$chessPosition->Move($side, $moveText);
      $chessPosition->Move('?', $moveText);  //The ? mean just do the move for the correct side.
      $this->FEN = $chessPosition->FEN();
      $this->Dirty = TRUE;
   } */

   /* private function ProcessReset($pwd) {
      if ($pwd!="ppp") throw new Exception("Invalid Password.");

      $this->FEN = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
      $this->GameState = "PLAYING";
      $this->WhiteComID = "";
      $this->WhitePlayer = "";
      $this->BlackComID = "";
      $this->BlackPlayer = "";
      $this->Dirty = TRUE;
   } */

   /*
   private function ProcessJoin($comID, $actionDetails) {
      
      if ($this->IdentifyPlayer($comID) != "?") throw new Exception("User already joined in table.");
      
      $detailItems = explode("|", $actionDetails);
      $side = $detailItems[0];
      $playerName = $detailItems[1];  
 
      switch($side) {
         case "W":
            if ($this->WhitePlayer!="") throw new Exception("Position is already occupied.");
            $this->WhiteComID = $comID;
            $this->WhitePlayer = $playerName;
            break;
         case "B":
            if ($this->BlackPlayer!="") throw new Exception("Position is already occupied.");
            $this->BlackComID = $comID;
            $this->BlackPlayer = $playerName;
            break;
         default:
            throw new Exception("Invalid side.");
      }
      
      $this->Dirty = TRUE;
   } */

   /*
   public function ProcessLeave($comID) {
 
      switch($this->IdentifyPlayer($comID)) {
         case "W":
            $this->WhiteComID = "";
            $this->WhitePlayer = "";
            break;
         case "B":
            $this->BlackComID = "";
            $this->WhitePlayer = "";
            break;
         default:
            throw new Exception("Unrecognized Player");
      } 
      $this->Dirty = TRUE;
   }*/
}

?>