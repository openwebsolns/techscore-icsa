<?php
use \utils\SailorSearcher;
use \data\SailorPlaceFinishDisplay;

/*
 * Very specific ICSA All-Academic report based on input CSV file.
 *
 * The input CSV must contain the following fields in the following order:
 *
 *  1. sailor ID
 *  2. sailor first name
 *  3. sailor last_name
 *
 * Edit the variables inline and re-run, unless you want to spend time
 * generalizing.
 *
 * INFILE = CSV that contains the columns first and last name.
 * SEASONS = The seasons (IDs) to look in.
 * OUTFILE = Where to write the chosen CSV
 */

$INFILE = '/tmp/file.csv';
$SEASONS = array(); //array('f15', 's16');
$OUTFILE = '/tmp/outfile.csv';
$TS_PATH = '/home/dayan/projects/techscore/techscore-web'; //'/src/techscore';

// ------------------------------------------------------------
require_once($TS_PATH . '/lib/conf.php');

function getRecord(Sailor $sailor, Regatta $regatta) {
  $display = new SailorPlaceFinishDisplay($sailor, $regatta);
  $places = $display->places();
  if (count($places) == 0) {
    return '--';
  }
  return implode("/", $places);
}

$in = fopen($INFILE, 'r');
$out = fopen($OUTFILE, 'w');

while (($fields = fgetcsv($in)) !== false) {
  $fname = $fields[1];
  $lname = $fields[2];
  $query = sprintf('%s %s', $fname, $lname);

  $searcher = new SailorSearcher();
  $searcher->setQuery($query);
  $results = $searcher->doSearch();
  if (count($results) != 1) {
    printf("Error: unable to find single sailor entry for \"%s\".\n", $query);
    continue;
  }

  $sailor = $results[0];
  $row1 = array($sailor->id, $sailor->first_name, $sailor->last_name);
  $row2 = array('', '', '');
  foreach ($sailor->getRegattas() as $regatta) {
    $season = $regatta->getSeason();
    if (count($SEASONS) == 0 || in_array($season->shortString(), $SEASONS)) {
      $row1[] = sprintf('%s (%s)', $regatta->name, $season->fullString());
      $row2[] = getRecord($sailor, $regatta);
    }
  }

  fputcsv($out, $row1);
  fputcsv($out, $row2);
}

fclose($in);
fclose($out);
