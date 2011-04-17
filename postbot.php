<?php

//electroly's thread parsing
require_once 'include/Global.php';
require_once 'shackapi.php';

require_once 'post.php';
require_once 'birthdayPost.php';
require_once 'infPost.php';
require_once 'lolPost.php';
require_once 'tagPost.php';
require_once 'unfPost.php';
require_once 'awardPost.php';


class PostBot {
    public $username;
    public $password;

    public $parentId;

    private $posts = array();
    private $sleeptime;
    private $debugMode;

    public function __construct($username, $password, $sleeptime=120, $parentId = null) {
        $this->username = $username;
        $this->password = $password;

        //default sleeptime between posts
        $this->sleeptime = $sleeptime;

        //override parentId if testing
        $this->parentId = $parentId;

        $this->setRootPost();
    }

    public function setRootPost() {
        $p = new Post('');
        $dayth = $p->ord_suf(date('z')+1);
        $body = "*[y{Today is ".date('l\, \t\h\e jS \o\f F').", the {$dayth} day of ".date('Y').".}y]*\n";

        $body .= system("curl -Is slashdot.org | egrep '^X-(F|B|L)' | sed s/^X-//");

        //TODO create quote database to use here
        $body .= "\n\n";
        // $body .= "This is the Best Of shacknews:";
        $body .= $this->insertDukeRelease();
        $body .= $this->insertShackconRelease();

        $p->body = $body;
        //make first post and override parentId
        $this->parentId = $this->post($p);
    }

    public function makePosts() {
        $this->addAwardPost();

        //post all posts in the pool
        foreach($this->posts as $p) {
            print "sleeping for {$this->sleeptime} seconds\n";
            sleep($this->sleeptime);
            print "posting {$p}\n";
            print "http://www.shacknews.com/chatty?id=" . $this->post($p);
        }
    }

    public function addAwardPost() {
        $awardPost = new AwardPost($this->posts);

        if($awardPost->checkAwardWinner()) {
            print "THERE ARE AWARDS!\n";
            $this->addPost($awardPost);
        }
    }

    public function addPost($post) {
        //add a post to the pool
        array_push($this->posts, $post);
    }

    private function post($post) {
        try {
            return ShackApi::post($this->username, $this->password, $post->body, $this->parentId);
        } catch (Exception $e) {
            while (true) {
                print "sleeping 300 secs\n";
                sleep(300);
                return ShackApi::post($this->username, $this->password, $post->body, $this->parentId);
            }
        }
    }

    private function insertDukeRelease() {
        $launch_date = mktime(0, 0, 0, 6, 14, 2011, 0);
        $today = time();
        $difference = $launch_date - $today;
        if ($difference > 0) {
            return "There are /[OMG]/ ". ceil($difference/60/60/24) ." days until DNF is released!\n";
        } elseif ($difference == 0) {
            return "HOLY SHIT IT'S TIME TO KICK ASS AND CHEW BUBBLE GUM! DNF IS RELEASED!!\n";
        }
    }

    private function insertShackconRelease() {
        $launch_date = mktime(0, 0, 0, 7, 8, 2011, 0);
        $today = time();
        $difference = $launch_date - $today;
        if ($difference > 0) {
            return "There are ". ceil($difference/60/60/24) ." days until Shackcon\n";
        } elseif ($difference == 0) {
            return "ZOMG VEGAS SHACKCON!!!\n";
        }
    }
}
?>
