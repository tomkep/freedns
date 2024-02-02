<?php

/*
  This file is part of XName.org project
  See  http://www.xname.org/ for details

  License: GPLv2
  See LICENSE file, or http://www.gnu.org/copyleft/gpl.html

  Author(s): Yann Hirou <hirou@xname.org>

*/

/*
 *  Generic interface for database access
 *   some code taken from daCode project http://www.dacode.org,
 *  originally from Fabien Seisen <seisen@linuxfr.org>
 */

/**
 * Generic interface for DB access. Currently supports mysql only
 *
 *@access public
 */
class Db {
  var $dbh, $sh;
  var $result;
  var $lastquery;
  var $cachecontent;
  var $totaltime;

  /**
   * Class constructor. Connects to DB
   *
   *@access public
   *@return object DB database object
   */
  function __construct() {
    global $config;

    $this->totaltime = 0;
    if ($config->dbpersistent) {
      $this->dbh = $this->pconnect($config->dbhost . ":" . $config->dbport,
                                   $config->dbuser,
                                   $config->dbpass,
                                   $config->dbname);
    } else {
      $this->dbh = $this->connect($config->dbhost,
                                  $config->dbuser,
                                  $config->dbpass,
                                  $config->dbname);
    }
    return $this->dbh;
  }

  /**
   * Do simple connect
   *
   *@access private
   *@param string $host hostname or IP of DB host
   *@param string $user username for db access
   *@param string $pass password for db access
   *@param string $db database name
   *@return object Db database handler
   */
  function connect($host, $user, $pass, $db) {
    $this->sh = new mysqli($host, $user, $pass);
    $res = $this->sh->select_db($db);
    return $res;
  }

  /**
   * Do permanent connect
   *
   *@access private
   *@param string $host hostname or IP of DB host
   *@param string $user username for db access
   *@param string $pass password for db access
   *@param string $db database name
   *@return object Db database handler
   */
  function pconnect($host, $user, $pass, $db) {
    return $this->connect("p:" . $host, $user, $pass, $db);
  }

  /**
   * Pass query to DB
   *
   *@access public
   *@param string $string QUERY
   *@return object query handler
   */
  function query($string, $cache = 0) {
    $string = preg_replace('/[\n\s\t]+?/', ' ', $string);
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $tstart = $mtime;
    if ($cache && $this->cachecontent && $this->cachecontent[$string]) {
      $this->result = $this->cachecontent[$string];
    } else {
      $this->result = $this->sh->query($string);
    }
    if ($cache) {
      $this->cachecontent[$string] = $this->result;
    }
    $mtime = microtime();
    $mtime = explode(" ",$mtime);
    $mtime = $mtime[1] + $mtime[0];
    $tend = $mtime;
    $ttot = $tend - $tstart;
    $this->lastquery = $string;
    $this->totaltime += $ttot;
    return $this->result;
  }

  /**
   * Fetch next row from query handler
   *
   *@access public
   *@param object Query $res query handler
   *@return array next result row
   */
  function fetch_row($res) {
    if ($res) {
      return $this->result->fetch_row();
    }
    return 0;
  }

  /**
   * Returns number of affected rows by given query handler
   *
   *@access public
   *@param object Query $res query handler
   *@return int number of affected rows
   */
  function affected_rows($res) {
    if ($res) {
      return $res->affected_rows;
    }
    return 0;
  }

  /**
   * Returns number of affected rows by given query handler (select only)
   *
   *@access public
   *@param object Query $res query handler
   *@return int number of affected rows
   */
  function num_rows($res) {
    if ($res) {
      return $res->num_rows;
    }
    return 0;
  }

  /**
   * Free current db handlers - query & results
   *
   *@access public
   *@return int 0
   */
  function free() {
    return 0;
  }


  /**
   * Check if an error occured, & take action
   *
   *@access public
   *@return int 1 if error, 0 else
   */
  function error() {
    global $config, $l;

    if ($this->sh->errno) {
      mailer($config->emailfrom,
             $config->emailto,
             $config->sitename . $l['str_trouble_with_db'],
             '',
             $this->sh->errno . ": " . $this->sh->error . "\n" . $this->lastquery . "\n");
      return 1;
    }
    return 0;
  }
}
?>
