///////////////////////////////////////////////////////////////////////////////
//////////////////////////----GLOBAL VARIABLES----/////////////////////////////
///////////////////////////////////////////////////////////////////////////////
//USER PROPERTIES:
var GUID = 0;

//ErikW

//BOARD PROPERTIES:
var HEIGHT = 16;
var WIDTH = 16;
var SQUARE_SIZE = 40;
var LIGHT_SQUARE_COLOR = "#8B8D7A";
var DARK_SQUARE_COLOR = "#006400";
var WALL_COLOR = "#000000";
var HIGHLIGHT_COLOR = "#AA0000";

var squares = new Array(HEIGHT * WIDTH);
var current_square = new Coord();

var counter = 0;
var request_timer = setInterval(timerUpdate, 10)

var debug_state = "";

function timerUpdate() {
    requestUpdate("STATE", "");
}

function Square() {
    this.type = '0';
    this.player = '0';
    this.time_left = 0;
}

function Square(type, player, time_left) {
    this.type = type;
    this.player = player;
    this.time_left = time_left;
}

function Coord(){
    this.row = 0;
    this.col = 0;
}

//IMAGES:
var w_pawn = new Image(); w_pawn.src = "Images/w_pawn.png";
var b_pawn = new Image(); b_pawn.src = "Images/b_pawn.png";
var w_knight = new Image(); w_knight.src = "Images/w_knight.png";
var b_knight = new Image(); b_knight.src = "Images/b_knight.png";
var w_bishop = new Image(); w_bishop.src = "Images/w_bishop.png";
var b_bishop = new Image(); b_bishop.src = "Images/b_bishop.png";
var w_rook = new Image(); w_rook.src = "Images/w_rook.png";
var b_rook = new Image(); b_rook.src = "Images/b_rook.png";
var w_queen = new Image(); w_queen.src = "Images/w_queen.png";
var b_queen = new Image(); b_queen.src = "Images/b_queen.png";
var w_king = new Image(); w_king.src = "Images/w_king.png";
var b_king = new Image(); b_king.src = "Images/b_king.png";

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////----GRAPHICS----////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
function init_board() {
    //Add mouse listener:
    board = document.getElementById("board");
    board.addEventListener("click", processMouseEvent, false);
    
    //Initialize board:
    //setBoard(STARTING_POSITION);
    update();
}

function update() {
    document.getElementById("debugOutput").innerHTML = debug_state;
    draw_squares();

}

function draw_squares() {
    var board = document.getElementById("board");
    var board_2d = board.getContext("2d");
    
    var temp_square;
    
    for (var row = 0; row <= HEIGHT; row++) {
        for (var col = 0; col <= WIDTH; col++) {
            //Background:
            board_2d.fillStyle = ((row + col) % 2 == 0) ? LIGHT_SQUARE_COLOR : DARK_SQUARE_COLOR;
            if (row === current_square.row && col === current_square.col) {
                board_2d.fillStyle = HIGHLIGHT_COLOR;
            }
            board_2d.fillRect(col * SQUARE_SIZE, row * SQUARE_SIZE, SQUARE_SIZE, SQUARE_SIZE);
            
            temp_square = squares[row * WIDTH + col];
            //Walls:
            if (temp_square.type === 'w') {
                board_2d.fillStyle = WALL_COLOR; 
                board_2d.fillRect(col * SQUARE_SIZE, row * SQUARE_SIZE, SQUARE_SIZE, SQUARE_SIZE);
            }
            //Pieces:
            else if (temp_square.type !== '0') {
                board_2d.drawImage(getImage(temp_square.player, temp_square.type), col * SQUARE_SIZE, row * SQUARE_SIZE, SQUARE_SIZE, SQUARE_SIZE);
            }
        }
    }
    

    
}

function getImage(color, type) {
    if (color === '0') {
        switch (type) {
            case 'p': return b_pawn;
            case 'n': return b_knight;
            case 'b': return b_bishop;
            case 'r': return b_rook;
            case 'q': return b_queen;
            case 'k': return b_king;
        }
    }
    else {
        switch (type) {
            case 'p': return w_pawn;
            case 'n': return w_knight;
            case 'b': return w_bishop;
            case 'r': return w_rook;
            case 'q': return w_queen;
            case 'k': return w_king;
        }
    }
}
///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////----INPUT----/////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
function setBoard(info_string) {
    var arrayCount = 0;
    var stringCount = 0;
    
    info_string = info_string.replace(/\n/g, '');
    info_string = info_string.replace(/\r/g, '');
    //info_string = info_string.replace(' ', '');
    alert(info_string);
    while (stringCount < info_string.length && arrayCount < HEIGHT * WIDTH) {
        squares[arrayCount] = new Square();
        squares[arrayCount].type = info_string[stringCount++];
        if (squares[arrayCount].type !== '0' && squares[arrayCount].type !== 'w') {
            squares[arrayCount].player = info_string[stringCount++];
            squares[arrayCount].time_left = info_string[stringCount++];
        }
        arrayCount++;
    }
}

///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////----OUTPUT----////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

function requestUpdate(type, details) {
    var request = new XMLHttpRequest();
    request.onreadystatechange = function process_request() {
        if (request.readyState === 4 && request.status === 200){
            var response = request.responseText.split("|");
            debug_state = request.responseText;
            if (response[0] === "SUCCESS") {
                setBoard(response[2]);
                update();
            }
            else {
                //alert(response[0] + ", Reason: " + response[1]);
            }
        }
    }
    request.open("GET", "SS/GameRequest.php?GUID=" + GUID + "&action=" + type + "&counter=" + (counter++), true);
    request.send();
}

///////////////////////////////////////////////////////////////////////////////
////////////////////////////----MOVE PROCESSING----////////////////////////////
///////////////////////////////////////////////////////////////////////////////
function processMouseEvent(m_e) {
    if (!m_e) {
        alert("No mouse event parameter");
        m_e = window.event;
    }
    var board = document.getElementById("board")
    var bounds = board.getBoundingClientRect();
    var border_width = 10;
    var col = Math.floor((m_e.clientX - bounds.left - border_width) / SQUARE_SIZE); 
    var row = Math.floor((m_e.clientY - bounds.top - border_width) / SQUARE_SIZE);
    processMove(row, col);
    update();
}

function processMove(row, col) {
    var square = squares[current_square.row * WIDTH + current_square.col];
    if (isLegal(current_square.row, current_square.col, row, col, square.type)) {
        squares[row * WIDTH + col] = new Square(square.type, square.player, 0);
        squares[current_square.row * WIDTH + current_square.col] = new Square();
        squares[current_square.row * WIDTH + current_square.col].type = '0';
        //requestUpdate();
    }
    
    current_square.col = col;
    current_square.row = row;
    
}
    
function isLegal(start_row, start_col, end_row, end_col, piece) {
    //alert(piece);
    if (piece === '0' || piece === 'w') return false;
    if (squares[end_row * WIDTH + end_col].player === squares[start_row * WIDTH + start_col].player) return false;
    switch (piece) {
        case '0': case 'w': return false;
        case 'p': return ((start_row === end_row && Math.abs(start_col - end_col) === 1) || (start_col === end_col && Math.abs(start_row - end_row) === 1));
        case 'n': return true;
        
    }
}

