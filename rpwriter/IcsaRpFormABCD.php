<?php
/*
 * This file is part of TechScore
 *
 * @package rpwriter
 */

require_once('rpwriter/AbstractRpBlockForm.php');

/**
 * RP Form for (four divisions) {
 }
 *
 * @author Dayan Paez
 * @version 2010-02-08
 */
class IcsaRpFormABCD extends AbstractRpBlockForm {

  const BLOCKS_PER_PAGE = 2;

  protected $num_skipper_A = 3;
  protected $num_skipper_B = 3;
  protected $num_skipper_C = 3;
  protected $num_skipper_D = 3;
  protected $num_crew_A = 3;
  protected $num_crew_B = 3;
  protected $num_crew_C = 3;
  protected $num_crew_D = 3;

  /**
   * Returns the LaTeX code for the body of this form
   *
   * @return String the LaTeX code
   */
  protected function draw($regatta_name, $host, $date, Array $blocks) {
    $pics = array();
    $within_page = 0;
    $fmt = '\put(%0.2f, %0.2f){%s}';
    $blocks_per_team = array();
    foreach ($blocks as $block) {
      if ($within_page == 0) {
        $pc = new LatexPic(-0.25, 0);
        $pc->add(sprintf('\put(7.05, 10.33){\thepage} ' .
                         '\put(7.50, 10.33){**num_pages**} ' .
                         '\put(1.75,  9.98){%s} ' .
                         '\put(4.25,  9.98){%s} ' .
                         '\put(6.55,  9.98){%s} ',
                         $regatta_name,
                         $host,
                         $date));
        $pics[] = $pc;
      }

      if (!isset($blocks_per_team[$block->team->id]))
        $blocks_per_team[$block->team->id] = 0;
      $blocks_per_team[$block->team->id]++;

      // - team and representative
      $name = sprintf("%s %s", $block->team->school->nick_name, $block->team->name);
      if ($blocks_per_team[$block->team->id] > 1)
        $name .= sprintf(" (%d)", $blocks_per_team[$block->team->id]);
      $team_X = 1.25;
      $team_Y = 9.65 - 4.42 * $within_page;
      $pc->add(sprintf($fmt, $team_X, $team_Y, $name));
      $pc->add(sprintf($fmt, $team_X + 4.6, $team_Y, $block->representative));

      // - write content: skippers for divisions A/B
      $X = 0.75;
      $Y = 8.55 - 4.42 * $within_page;
      // :A then :B
      foreach (array("skipper_A", "skipper_B") as $div_num => $div) {
        $x = $X + (3.5 * $div_num);
        foreach ($block->$div as $i => $s) {
          $y = $Y - (0.3 * $i);
          $year = substr($s->getSailorYear(), 2);
          $races = DB::makeRange($s->races_nums);
          $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
          $pc->add(sprintf($fmt, $x + 1.9,  $y, $year));
          $pc->add(sprintf($fmt, $x + 2.33, $y, $races));
        }
      }

      // crews for divisions A/B
      $X = 0.75;
      $Y = 7.65 - 4.42 * $within_page;
      foreach (array("crew_A", "crew_B") as $div_num => $div) {
        $x = $X + (3.5 * $div_num);
        foreach ($block->$div as $i => $s) {
          $y = $Y - (0.3 * $i);
          $year = substr($s->getSailorYear(), 2);
          $races = DB::makeRange($s->races_nums);
          $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
          $pc->add(sprintf($fmt, $x + 1.9,  $y, $year));
          $pc->add(sprintf($fmt, $x + 2.33, $y, $races));
        }
      }

      // skippers for division C
      $X = 0.75;
      $Y = 6.23 - 4.42 * $within_page;
      foreach ($block->skipper_C as $i => $s) {
        $x = $X;
        $y = $Y - (0.3 * $i);
        $year = substr($s->getSailorYear(), 2);
        $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $x + 1.9,  $y, $year));
        $pc->add(sprintf($fmt, $x + 2.33, $y, $races));
      }

      // skippers for division D
      $X = 4.25;
      $Y = 6.23 - 4.42 * $within_page;
      foreach ($block->skipper_D as $i => $s) {
        $x = $X;
        $y = $Y - (0.3 * $i);
        $year = substr($s->getSailorYear(), 2);
        $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $x + 1.9,  $y, $year));
        $pc->add(sprintf($fmt, $x + 2.33, $y, $races));
      }

      // - update within page
      $within_page = ($within_page + 1) % self::BLOCKS_PER_PAGE;
  } // end of blocks

  $inc = $this->getIncludeGraphics();
  $pages = array();
  foreach ($pics as $pic)
    $pages[] = sprintf("%s %s", $inc, $pic);

  $body = implode('\clearpage ', $pages);
  $body = str_replace("**num_pages**", count($pages), $body);
  return str_replace("&", "\&", $body);
}

public function getPdfName() {
  return __DIR__ . '/ICSA-RP-ABCD.pdf';
}
}
?>