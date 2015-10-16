<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * aktestox implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * aktestox.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class aktestox extends Table
{
	function aktestox( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );
        
	}
	
    protected function getGameName( )
    {
        return "aktestox";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ffffff", "000000" );
        //$default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );

 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = array();
        list( $blackplayer_id, $whiteplayer_id ) = array_keys( $players );
        for( $x=1; $x<=3; $x++ )
        {
            for( $y=1; $y<=3; $y++ )
            {
                $token_value = "NULL";
                $sql_values[] = "('$x','$y',$token_value)";
            }
        }
        $sql .= implode( $sql_values, ',' );
        self::DbQuery( $sql );
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        $result['board'] = self::getObjectListFromDB( "SELECT board_x, board_y, board_player FROM board");
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getPossibleMoves($playerID)
    {
        $possible = array();

        //return an array of all the null spaces...
        $board = self::getObjectListFromDB( "SELECT board_x, board_y, board_player FROM board");

        //self::debug("current player is " . self::getCurrentPlayerId());
        self::debug("getting possible moves for " . $playerID);

        //if (self::getCurrentPlayerId() != $playerID)
        //{
        //    return $possible;
        //}

        for ($i = 0; $i < count($board); $i++)
        {
            if ($board[$i]["board_player"] == '')
            {
                array_push($possible, array("x" => $board[$i]["board_x"], "y" => $board[$i]["board_y"]));
            }
        }

        self::debug("got possible moves");
        
        return $possible;
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in aktestox.action.php)
    */

    function playDisc( $x, $y )
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'playDisc' );  

        //... at first, we check that this action is possible according to current game state (see "possible action"). We already did it on client side, but it's important to do it on server side too (otherwise it would be possible to cheat).

        // Now, check if this is a possible move
        //$board = self::getBoard();
        $player_id = self::getActivePlayerId();
        $possibleMoves = self::getPossibleMoves($player_id);
        $validMove = false;

        for ($i = 0; $i < count($possibleMoves); $i++) {
            if ($possibleMoves[$i]["x"] == $x && $possibleMoves[$i]["y"] == $y)
            {
                $validMove = true;                
            }
        }

        if ($validMove == false)
        {
            throw new feException( "Invalid move" );
        }

        //move is ok, add it and then check for win
        $sql = "UPDATE board SET board_player='$player_id' WHERE board_x = '$x' AND board_y = '$y'";
        self::DbQuery( $sql );
       
        // Notify
        self::notifyAllPlayers( "playDisc", $this->text['playDisc'], array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'x' => $x,
            'y' => $y
        ) );

        $this->gamestate->nextState( 'playDisc' );        
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function argPlayerTurn()
    {
        return array(
            'possibleMoves' => self::getPossibleMoves( self::getActivePlayerId() )
        );
    }

    function stNextPlayer()
    {
        // Activate next player
        $player_id = self::activeNextPlayer();
        
        self::giveExtraTime( $player_id );

        //a win is three in a row on a horizontal, diagonal, or vertical
        $board = self::getObjectListFromDB( "SELECT board_x, board_y, board_player FROM board");
        
        //convert into a 2d array
        $board2d = array(
            array(0,0,0),
            array(0,0,0),
            array(0,0,0)
            );
        
        for ($i=0; $i<count($board);$i++)
        {
            $x1 = intval($board[$i]["board_x"]);
            self::debug("x : ".$x1);
            $y1 = intval($board[$i]["board_y"]);
            self::debug("y : ".$y1);
            $player = $board[$i]["board_player"];
            self::debug("player : ".$player);

            $board2d[$x1-1][$y1-1] = $player;
        }

        self::debug("converting done, checking for win");
        
        //now check for win
        $winner = "";
        for ($row = 0; $row < 3; $row++)
        {
            if ($board2d[0][$row] == $board2d[1][$row] && $board2d[1][$row] == $board2d[2][$row] && $board2d[0][$row] != 0)
            {
                $winner = $board2d[0][$row];
            }
        }
        for ($col = 0; $col < 3; $col++)
        {
            if ($board2d[$col][0] == $board2d[$col][1] && $board2d[$col][1] == $board2d[$col][2] && $board2d[$col][0] != 0)
            {
                $winner = $board2d[$col][0];
            }
        }
        if ($board2d[0][0] == $board2d[1][1] && $board2d[1][1] == $board2d[2][2] && $board2d[0][0] != 0)
        {
            $winner = $board2d[0][0];
        }
        if ($board2d[2][0] == $board2d[1][1] && $board2d[1][1] == $board2d[0][2] && $board2d[2][0] != 0)
        {
            $winner = $board2d[2][0];
        }

        // Then, go to the next state
        if ($winner != "")
        {     
            //update score in db for winner
            $sql = "UPDATE player
                    SET player_score = 1 WHERE player_id=".$winner;
            self::DbQuery( $sql );
            self::debug("WINNAR:".$winner);
            $this->gamestate->nextState( 'endGame' );
        }
        else if (count(self::getPossibleMoves($player_id))==0)
        {
            self::debug("no moves left, drawn game");
            $this->gamestate->nextState( 'endGame' );
        }
        else
        {
            self::debug("no winner, next move");
            $this->gamestate->nextState( 'nextTurn' ); 
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
