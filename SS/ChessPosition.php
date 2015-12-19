<?php
class ChessPosition {

//***************************************************************************************************************************
//Class Member Variables:

   private $squareOccupations;         //Currently this is encoded as an arrray of strings, one for each rank.  It's an expanded form the each FEN row (i.e. no numeric abbreviations).  A _ means an empty square.
   private $sideToMove;                //'W' or 'B'
   private $whiteCanCastleKingside;    //Boolean
   private $whiteCanCastleQueenside;   //Boolean
   private $blackCanCastleKingside;    //Boolean
   private $blackCanCastleQueenside;   //Boolean

   //TODO: En Passant

   private $halfMoveClock;             //Integer
   private $moveNumber;                //Integer

//***************************************************************************************************************************
//Constructor:

   public function __construct($FEN) {
      $fenItems = explode(" ", $FEN);

      $this->squareOccupations = $this->FENPositionToOccupationArray($fenItems[0]);
      
      $this->sideToMove = strtoupper($fenItems[1]);
    
      $this->whiteCanCastleKingside = (strpos($fenItems[2], 'K')!==FALSE);
      $this->whiteCanCastleQueenside = (strpos($fenItems[2], 'Q')!==FALSE);
      $this->blackCanCastleKingside = (strpos($fenItems[2], 'k')!==FALSE);
      $this->blackCanCastleKingside = (strpos($fenItems[2], 'q')!==FALSE);

      //TODO: En Passant

      $this->halfMoveClock = $fenItems[4];
      $this->moveNumber = $fenItems[5];
   }

//***************************************************************************************************************************
//Movement:

   public function Move($sideToMove, $moveText) {
      
      //Make sure it's the correct side trying to move:
      if ($sideToMove=='?') $sideToMove=$this->sideToMove; 
      if ($sideToMove!=$this->sideToMove) throw new Exception("Invalid side to move.");
      
      //Parse the moveText into the source and destination square information:
      $this->ParseMoveText($moveText, $sourceRank, $sourceFile, $destRank, $destFile);

      //The source and destination squares cannot be the same:
      if (($sourceRank==$destRank)&&($sourceFile==$destRank)) throw new Exception("Source and destination squares cannot be the same.");

      //Get the source piece and make sure it's the correct color:
      $piece = $this->SquareContents($sourceRank, $sourceFile);
      if ($piece=='_') throw new Exception("Source square is empty.");
      if ($this->PieceColor($piece)!=$sideToMove) throw new Exception("There is not a piece of the correct color in the specified source square.");

      //The destination square should not contain a piece of the same color:
      if ($this->SquareContainsPiece($destRank, $destFile, $sideToMove)) throw new Exception("The destination square contains a piece of the wrong color.");

      //Dispatch out to an auxiliary routine to do additional checks for each of the types of pieces that can be moved.  Each returns TRUE if the move is valid:
      switch(strtoupper($piece)) {
         case 'P': $moveValid = $this->CheckMovePawn($sourceRank, $sourceFile, $destRank, $destFile); break;
         case 'N': $moveValid = $this->CheckMoveKnight($sourceRank, $sourceFile, $destRank, $destFile); break;
         case 'B': $moveValid = $this->CheckMoveBishop($sourceRank, $sourceFile, $destRank, $destFile); break;
         default:  throw new Exception("Unsupported piece {$piece}."); 
      }
      if (!$moveValid) throw new Exception('Illegal move.');
      
      //Actually do the piece movement, putting the piece in the destination square and clearing out the source square.
      //Keep in mind I can STILL abort the operation after this step by throwing an exception and in that case the updated chess position object should not be used.
      $this->WriteToSquare($destRank, $destFile, $piece);
      $this->WriteToSquare($sourceRank, $sourceFile, "_");
      
      //Now that this is all done, make sure the square the king is on is not attacked (that is the move didn't put himself into check):
      $this->FindKing('@', $kingRank, $kingFile);
      if ($this->SquareIsAttacked($kingRank, $kingFile, "!")) throw new Exception("Move leaves king in check.");
      
      //Advance the half-move clock, the move counter, and whose side it is to move:
      if ((strtoupper($piece)=='P')||(!$this->SquareIsEmpty($destRank, $destFile))) $this->halfMoveClock=0; else $this->halfMoveClock++;
      if ($sideToMove=='B') $this->moveNumber++;
      $this->sideToMove = ($this->sideToMove=='W') ? 'B' : 'W';
        
   }

   //Currently no support for enpassant.
   private function CheckMovePawn($sourceRank, $sourceFile, $destRank, $destFile) {
       
      $dRank = ($destRank-$sourceRank);
      $dFile = abs($destFile-$sourceFile);

      switch($dFile) {
         case 0:
            //If we move in the same file, we can't capture:
            if(!$this->SquareIsEmpty($destRank, $destFile)) return FALSE;

            //We can move one square, except for the 2 square first move for the pawn:
            switch($dRank) {
               case -1: return ($this->sideToMove=='B');
               case -2: return (($sourceRank==7)&&($this->sideToMove=='B')&&($this->SquareIsEmpty($sourceRank-1, $destFile)));
               case +1: return ($this->sideToMove=='W');
               case +2: return (($sourceRank==2)&&($this->sideToMove=='W')&&($this->SquareIsEmpty($sourceRank+1, $destFile)));
               default: return FALSE;
            }  

         case 1:
            //If we move to the next file over, there must be a pawn to capture (except En Passant which isn't yet implemented.)
            if (!$this->SquareContainsPiece($destRank, $destFile, '!')) return FALSE;
            return TRUE;

         default:
            return FALSE;
      }
   }

   private function CheckMoveKnight($sourceRank, $sourceFile, $destRank, $destFile) {
      $dRank = abs($destRank-$sourceRank);
      $dFile = abs($destFile-$sourceFile);
      return ($dRank*$dFile==2);
   }

   private function CheckMoveBishop($sourceRank, $sourceFile, $destRank, $destFile) {
      $dRank = abs($destRank-$sourceRank);
      $dFile = abs($destFile-$sourceFile);
      if ($dRank!=$dFile) return FALSE;
      return $this->CheckIfLineIsOpen($sourceRank, $sourceFile, $destRank, $destFile);
   }

   //Checks if the "line" (along a row, a file, or a diagonal) has no pieces in between the source and destination squares.
   //This does NOT include the source or the destination squares themseves.
   private function CheckIfLineIsOpen($sourceRank, $sourceFile, $destRank, $destFile) {
      
      //Make sure the source and destination squares are in fact along a rank, a file, or a diagonal:
      $dRank = ($destRank-$sourceRank);
      $dFile = ($destFile-$sourceFile);
      if (($dRank==0)&&($dFile==0)) throw new Exception("Source and destination squares cannot be the same.");
      if (($dRank*$dFile!=0)&&(abs($dRank)!=abs($dFile))) throw new Exception("Illegal line.");

      //Get the number of squares we need to check.  Adjactent squares are always open (if the count is 0):
      $count = max(abs($dRank), abs($dFile)) - 1;
      if ($count==0) return TRUE;
    

      $deltaRank = $dRank / abs($dRank);
      $deltaFile = $dFile / abs($dFile);
      for ($i=1;$i<=$count;$i++) {
         $testRank = $sourceRank + ($i * $deltaRank); 
         $testFile = $sourceFile + ($i * $deltaFile);
         if ($this->SquareIsOccupied($testRank, $testFile)) return FALSE;
      }
      
      return TRUE;
   }


//***************************************************************************************************************************
//ParseMoveText:

   private function ParseMoveText($moveText, &$sourceRank, &$sourceFile, &$destRank, &$destFile) {
      if (strlen($moveText)!=4) throw new Exception("Invalid move: Text should always be exactly four characters long.");

      $sourceFile = $this->MoveTextCharacterToFile(substr($moveText,0,1));
      $sourceRank = $this->MoveTextCharacterToRank(substr($moveText,1,1));
      $destFile = $this->MoveTextCharacterToFile(substr($moveText,2,1));
      $destRank = $this->MoveTextCharacterToRank(substr($moveText,3,1));
   }

   private function MoveTextCharacterToFile($s) {
      $s = ord(strtoupper($s)) - ord("A") + 1;
      if(($s<1)||($s>8)) throw new Exception("Illegal file designation: '{$s}'.");
      return $s;
   }

   private function MoveTextCharacterToRank($s) {
      $s = (int)$s;
      if(($s<1)||($s>8)) throw new Exception("Illegal rank designation: '{$s}'.");
      return $s;
   }

//***************************************************************************************************************************
//Board Queries:

   private function ResolveColorSelector($color, $eitherSelectorAllowed) {
      switch($color) {
         case 'W': return 'W';
         case 'B': return 'B';
         case '?': if ($eitherSelectorAllowed) return '?'; else throw new Exception("Invalid Color Selector.");
         case '@': return $this->sideToMove;
         case '!': return ($this->sideToMove=='W') ? 'B' : 'W';
         default:  throw new Exception("Invalid Color Selector.");
      }
   }

   private function SquareContainsPiece($rank, $file, $color='?') {
      $color = $this->ResolveColorSelector($color, TRUE);
      $q = $this->SquareContents($rank, $file);
      $side = $this->PieceColor($q); 
      if ($color=='?') return ($side!='?'); else return ($side==$color);
   }

   private function SquareIsEmpty($rank, $file) {
      return($this->SquareContents($rank,$file)=='_');
   }

   private function SquareIsOccupied($rank, $file) {
      return($this->SquareContents($rank,$file)!='_');
   }

   private function SquareContents($rank, $file) {
      return substr($this->squareOccupations[$rank-1], $file-1, 1);
   }

   private function WriteToSquare($rank, $file, $piece) {
      $this->squareOccupations[$rank-1] = substr_replace($this->squareOccupations[$rank-1], $piece, $file-1, 1);
   }

   private function PieceColor($piece) {
      if ($piece=='_') return "?";
      if (ctype_lower($piece)) return "B"; else return "W";
   }

   private function SquareIsAttacked($rank, $file, $byColor) {
      //TODO
      return FALSE;
   }

   private function FindKing($color, &$rank, &$file) {
      $color = $this->ResolveColorSelector($color, FALSE);
      $pieceToFind = ($color=='W') ? 'K' : 'k';
      
      for($r=1;$r<=8;$r++) {
         for($c=1;$c<=8;$c++) {
            if ($this->SquareContents($r,$c)==$pieceToFind) {
               $rank=$r;
               $file=$c;
               return;
            }
         }
      }

      throw new Exception("Couldn't find king.");
   }
   

//***************************************************************************************************************************
//FEN Construction and Parsing:

   public function FEN() {
      $rc = "";
      
      for($r=0;$r<8;$r++) {
         $rc .= $this->CompactFENRow($this->squareOccupations[7-$r]);
         if ($r<7) $rc .= "/"; 
      }
      
      $rc .= ' '.strtolower($this->sideToMove);

      $rc .= ' '.$this->FENCastlingRights();

      $rc .= ' -'; //TODO: En Passant

      $rc .= ' '.$this->halfMoveClock;
      $rc .= ' '.$this->moveNumber;

      return $rc;
   }

   private function FENCastlingRights() {
      $rc = "";

      if ($this->whiteCanCastleKingside) $rc .= "K";
      if ($this->whiteCanCastleQueenside) $rc .= "Q";
      if ($this->blackCanCastleKingside) $rc .= "k";
      if ($this->blackCanCastleQueenside) $rc .= "q";
      
      if ($rc ="") $rc = "-";
      return $rc;
   }

   private function FENPositionToOccupationArray($FENPositionDefinition) {
      $rows = explode("/", $FENPositionDefinition);
      if (count($rows)!=8) throw new Exception("Invalid FEN position.");
      for($r=0;$r<8;$r++) $rowsX[$r] = $this->FENRowToOccupationArray($rows[7-$r]);
      return $rowsX;
   }

   private function FENRowToOccupationArray($FENRowDefinition) {
      $rc = "";
      
      //Expand out any numeric abbreviations:
      for($i=0; $i<strlen($FENRowDefinition); $i++) {
         $s = substr($FENRowDefinition,$i,1);
         if(ctype_digit($s)) $s = str_repeat("_", $s);
         $rc = $rc . $s;
      }
      
      return $rc;
   }

   private function CompactFENRow($s) {
      for($r=8;$r>0;$r--) {
         $s = str_replace(str_repeat("_", $r), $r, $s);   
      }
      return $s;
   }
}
?>