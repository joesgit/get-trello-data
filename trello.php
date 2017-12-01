<?php

/**
 * Trello board things
 */
class TrelloData
{

  /**
   * Private class vars
   * @var $key string
   * @var $token string
   * @var $board string
   */
  private $key, $token, $board;

  /**
   * Constructor
   */
  function __construct()
  {
    $this->key     = '';
    $this->token   = '';
    $this->board   = '';
    $this->lists   = null;
    $this->members = null;
    $this->getListsAndMembers();
  }

  /**
   * get all available lists and members on a board
   * @return void
  **/
  public function getListsAndMembers()
  {
    $this->lists   = $this->getLists();
    $this->members = $this->getMembers();
  }

  /**
   * get all available lists on a board
   * @return array
  **/
  public function getLists()
  {
    return json_decode($this->sendRequest('/1/boards/'.$this->board.'/lists'));
  }

  /**
   * returns an array of list objects for each name ie. ['Inbox', 'Progress', 'Blocked']
   * @param array or string $name
   * @return array
  **/
  public function getListsByName($name)
  {
    if (is_array($name)) {
      return array_filter($this->lists, function ($v) use ($name) { return in_array($v->name, $name); });
    }
    return array_filter($this->lists, function ($v) use ($name) { return $v->name === $name; });
  }

  /**
   * get all available members on a board
   * @return array
  **/
  public function getMembers()
  {
    return json_decode($this->sendRequest('/1/board/'.$this->board.'/members'));
  }

  /**
   * returns an array of member objects for each username ie. ['is', 'super', 'cool']
   * @param array or string $name
   * @return array
  **/
  public function getMembersByName($username)
  {
    if (is_array($username)) {
      return array_filter($this->members, function($v) use ($username) { return in_array($v->username, $username); });
    }
    return array_filter($this->members, function($v) use ($username) { return $v->username === $username; });
  }

  /**
   * returns a member's avatar image url by id and size: 30, 50, or 170
   * @param string $id
   * @param string $size
   * @return string
  **/
  public function getMemberAvatar($id, $size = '170')
  {
    return 'https://trello-avatars.s3.amazonaws.com/' . reset(json_decode($this->sendRequest('/1/members/'.$id.'/avatarHash'))) . '/'.$size.'.png';
  }

  /**
   * returns an array of card objects for each list
   * @param array $lists
   * @return string
  **/
  public function getCards($lists)
  {
    return array_map(function($v) {
      return json_decode($this->sendRequest('/1/list/'.$v->id.'/cards'));
    }, $lists);
  }

  /**
   * returns an array of a card's comments
   * @param string $id
   * @return array
  **/
  public function getCardComments($id)
  {
    return array_filter(json_decode($this->sendRequest('/1/cards/'.$id.'/actions')), function($v) {
      return $v->type === 'commentCard';
    });
  }

  /**
   * get a list's key by name
   * @param string $name
   * @return string
  **/
  public function getListKey($name) {
    foreach ($this->lists as $key => $list) {
      if ($list->name === $name) {
        return $key;
      }
    }
    return false;
  }

  /**
   * helper function to make Trello API Calls
   * @param string $url endpoint
   * @param array $data post data
   * @return string
  **/
  private function sendRequest($action) {
    $params = array(
      'key'   => $this->key,
      'token' => $this->token,
    );
    
    $options = array(
      CURLOPT_HEADER         => false,
      CURLOPT_URL            => 'https://api.trello.com'. $action . '?' . http_build_query($params),
      CURLOPT_RETURNTRANSFER => true,
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
  }
}