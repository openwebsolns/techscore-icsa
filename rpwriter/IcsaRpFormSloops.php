<?php
/*
 * This file is part of TechScore
 *
 * @package rpwriter
 */

require_once('rpwriter/AbstractRpBlockForm.php');

/**
 * Class for writing RP forms for sloops
 *
 * @author Dayan Paez
 * @version 2010-02-08
 */
class IcsaRpFormSloops extends AbstractRpBlockForm {

  const BLOCKS_PER_PAGE = 2;

  protected $num_skipper_A = 2;
  protected $num_crew_A = 6;

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
                         '\put(1.75, 10.05){%s} ' .
                         '\put(4.25, 10.05){%s} ' .
                         '\put(6.55, 10.05){%s} ',
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
      $team_Y = 9.70 - 3.05 * $within_page;
      $pc->add(sprintf($fmt, $team_X, $team_Y, $name));
      $pc->add(sprintf($fmt, $team_X + 4.6, $team_Y, $block->representative));

      // - write content: skippers for divisions A
      $x = 0.50;
      $Y = 8.9 - 3.05 * $within_page;
      // :A
      foreach ($block->skipper_A as $i => $s) {
        $y = $Y - (0.25 * $i);
        $year = substr($s->getSailorYear(), 2);
        $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $x + 3.8,  $y, $year));
        $pc->add(sprintf($fmt, $x + 4.3, $y, $races));
      }

      // crews
      $x = 0.80;
      $Y = 8.38 - 3.05 * $within_page;
      foreach ($block->crew_A as $i => $s) {
        $y = $Y - (0.27 * $i);
        $year = substr($s->getSailorYear(), 2);
        $races = DB::makeRange($s->races_nums);
        $pc->add(sprintf($fmt, $x,        $y, $s->getSailorName()));
        $pc->add(sprintf($fmt, $x + 3.5,  $y, $year));
        $pc->add(sprintf($fmt, $x + 4.0, $y, $races));
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
    return __DIR__ . '/ICSA-RP-SLOOPS.pdf';
  }
}
?>