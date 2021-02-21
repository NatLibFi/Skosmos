<?php
/**
* SHA1Checker class creates a sha1 hash based on two environment variables (system time & self generated seed string) and checks if the posted sha1 hash matches with the generated sha1 hash.
*
* How:
* 1. Get a sha1 hash (from the host system).
* 2. The generated sha1 hash can be used in a twig template.
* 3. Check if the sha1 hash posted from the twig template and sha1 hash from the host system side match.
* 4. The result (boolean value) can be used in your business logic.
*
* Environment variables:
* export sha1seed="helou" / Ad hoc
* export timeforphp=$(date) / reassigning the variable can be scheduled
*
* Simple use case example:
* $canISendEmail = new SHA1Checker();
* echo $canISendEmail->getSHA1();
* echo $canISendEmail->checkSHA1Match("weird string") ? 'true' : 'false';
* echo $canISendEmail->checkSHA1Match($canISendEmail->getSHA1()) ? 'true' : 'false';
*/

class SHA1Checker {
  private $seed, $datex;

  function __construct() {
    $this->$seed = getenv('sha1seed');
    $this->$datex = getenv('timeforphp');
  }

  function getSHA1() {
    return sha1($this->$seed . $this->$datex);
  }

  function checkSHA1Match(string $stringToCheck): bool {
    if ($stringToCheck === $this->getSHA1()) {
      return true;
    }
    return false;
  }
}

?>
